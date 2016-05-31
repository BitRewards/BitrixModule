<?php
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/giftd.coupon/install/version.php');
IncludeModuleLangFile(__FILE__);

class GiftdHelper
{
    static public $MODULE_ID = 'giftd.coupon';

    static public $API_OPTIONS = array('API_KEY', 'USER_ID', 'PARTNER_CODE', 'PARTNER_TOKEN_PREFIX', 'SETTINGS', 'DISABLE_BASKET_RULES');
    static public $COMPONENT_OPTIONS = array('COMPONENT_IS_ACTIVE', 'COMPONENT_TEMPLATE', 'COMPONENT_TEMPLATE_JS_COUPON_FIELD_ID', 'COMPONENT_TEMPLATE_JS_CALLBACK');
    static public $TAB_OPTIONS = array('JS_TAB_IS_ACTIVE', 'JS_TAB_POSITION', 'JS_TAB_CUSTOMIZE', 'JS_TAB_OPTIONS');

    private static $_optionsCache = array();

    public static function isBitrixVersionNewerThen15()
    {
        return version_compare(SM_VERSION, "15.0.0") >= 0;
    }

    public static function CheckPatchOnBeforeProlog()
    {
        if (static::isBitrixVersionNewerThen15()) {
            return;
        }

        $cache = new CPHPCache();
        if (!$cache->InitCache(3600, $cacheId = 'giftd-discount-patch-check', '/') || isset($_REQUEST['giftd-discount-patch-check'])) {
            $cache->StartDataCache(3600, $cacheId, '/');
            $cache->EndDataCache(array('time' => time()));
            $patch_source = $_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/'.self::$MODULE_ID.'/general/discount_coupon.php';
            $patch_target = $_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/catalog/general/discount_coupon.php';

            $is_patched = strpos(file_get_contents($patch_target), 'giftdpatched') !== false;
            if(!$is_patched && self::IsSetModuleSettings()) {
                CopyDirFiles($patch_target, $_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/catalog/general/base_discount_coupon.php', true);
                CopyDirFiles($patch_source, $patch_target, true);
            }
        }
    }

    public static function getApiKey()
    {
        return self::GetOption('API_KEY');
    }

    public static function debug($arguments/*, ... */)
    {
        try {
            $args = func_get_args();
            $content = date("d.m.Y H:i:s") . ":\n";
            foreach ($args as $i => $arg) {
                $content .= "Argument #$i dump:\n" . (var_export($arg, true)) . "\n\n";
            }

            $client = new GiftdClient(
                self::GetOption('USER_ID'),
                self::GetOption('API_KEY')
            );

            $client->query('test/debug', array('data' => $content));
        } catch (Exception $e) {
            $client = new GiftdClient(null, null);

            $client->query('test/debug', array('data' => $e->getMessage()));
        }
    }

    public static function InjectJSPanelScriptOnBeforeProlog()
    {
        self::InjectJSTabScriptOnBeforeProlog();
    }

    public static function InjectJSTabScriptOnBeforeProlog()
    {
        global $APPLICATION;

        self::handleOrderCheckStatusQuery();

        if (self::IsSetModuleSettings() &&
            (!defined('ADMIN_SECTION') || !ADMIN_SECTION) &&
            (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/admin/') === false) &&
            !(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            && !defined('GIFTD_JS_CODE_INSERTED')
            ) {

            $APPLICATION->AddHeadString(self::getJSTabScript());

            define('GIFTD_JS_CODE_INSERTED', 1);
        }


    }

    public static function ReplaceTopWithParentOnAfterEpilog()
    {
        // empty
    }

    function MakeSettingArray($name, $type, $opt = '', $disabled = '')
    {
        return array($name, GetMessage($name), self::GetOption($name), array($type, $opt), $disabled);
    }

    public static function GetOption($key)
    {
        if ($key != 'SETTINGS' && SITE_ID && (!defined('ADMIN_SECTION') || !ADMIN_SECTION )) {
            $additionalSettings = GiftdHelper::GetOption('SETTINGS');
            if ($additionalSettings) {
                $additionalSettings = json_decode($additionalSettings, true);
                if (isset($additionalSettings['sites'][SITE_ID][$key])) {
                    return $additionalSettings['sites'][SITE_ID][$key];
                }
            }
        }
        if (!isset(self::$_optionsCache[$key])) {
            self::$_optionsCache[$key] = COption::GetOptionString(self::$MODULE_ID, $key);
        }
        return self::$_optionsCache[$key];
    }

    public static function SetOption($key, $value)
    {
        COption::SetOptionString(self::$MODULE_ID, $key, $value);
        unset(self::$_optionsCache[$key]);
    }

    function IsSetSettings($keys)
    {
        foreach($keys as $key)
            if(!self::GetOption($key))
                return false;

        return true;
    }

    function IsSetModuleSettings()
    {
        return self::IsSetSettings(array('API_KEY', 'USER_ID', 'PARTNER_CODE', 'PARTNER_TOKEN_PREFIX'));
    }

    function IsComponentActive()
    {
        return self::GetOption('COMPONENT_IS_ACTIVE') == 'Y';
    }

    function ComponentType()
    {
        return self::GetOption('COMPONENT_TEMPLATE');
    }

    function ComponentJSCouponFieldID()
    {
        return self::GetOption('COMPONENT_TEMPLATE_JS_COUPON_FIELD_ID');
    }

    function ComponentJSCouponValidationCallback()
    {
        return self::GetOption('COMPONENT_TEMPLATE_JS_CALLBACK');
    }

    function UpdateFromFile($key, $postfix)
    {
        if(is_array($_FILES[$key.$postfix]))
        {
            $image = $_FILES[$key.$postfix];
            if(strstr($image['type'], 'image/'))
            {
                $dst = BX_ROOT.'/modules/'.self::$MODULE_ID.'/img/'.$image['name'];
                move_uploaded_file($image['tmp_name'], $_SERVER['DOCUMENT_ROOT'].$dst);

                COption::SetOptionString(self::$MODULE_ID, $key.'_TYPE',$image['type']);
                COption::SetOptionString(self::$MODULE_ID, $key, $dst);

                if($image['size'] <= 3*1024)
                    COption::SetOptionString(self::$MODULE_ID, $key.'_BASE64', 'Y');
                else
                    COption::SetOptionString(self::$MODULE_ID, $key.'_BASE64', 'N');

                return $dst;
            }
        }

        return false;
    }

    public static function handleUninstall($api_key, $user_id)
    {
        $client = new GiftdClient($user_id, $api_key);
        try {
            $client->query('bitrix/uninstall', static::_getSiteData());
        } catch (Exception $e) {
            try {
                $client = new GiftdClient(null, null);
                $client->query('bitrix/uninstall', static::_getSiteData());
            } catch (Exception $e) {
                $from = COption::GetOptionString("main", "email_from");
                $headers = $from ? "From: $from" : null;
                mail("partner@giftd.ru", "Ошибка при деинсталляция модуля Giftd для Битрикса", $e->__toString(), $headers);
            }
        }
    }

    private static function _getSiteData()
    {
        global $USER;

        $schema = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http';

        $host = SITE_SERVER_NAME ?: (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
        if (count($hostParts = explode(":", $host)) > 1) {
            $port = $hostParts[1];
            if ($port == 80 || $port == 443) {
                $host = $hostParts[0];
            }
        }
        $url = "$schema://$host";

        $siteData = CSite::GetByID(SITE_ID)->Fetch();
        $userData = isset($USER) ? $USER->GetByID($USER->GetId())->Fetch() : null;

        return array(
            'email' => isset($USER) ? $USER->GetEmail() ?: COption::GetOptionString("main", "email_from") : null,
            'phone' => $userData ? (isset($userData['PERSONAL_PHONE']) ? $userData['PERSONAL_PHONE'] : $userData['WORK_PHONE']) : null,
            'name' => isset($USER) ? $USER->GetFullName() : null,
            'url' => $url,
            'title' => isset($siteData['SITE_NAME']) ? $siteData['SITE_NAME'] : null,
            'bitrix_module_version' => SM_VERSION,
        );
    }

    public static function QueryApi($method, $params = array())
    {
        $client = new GiftdClient(self::GetOption('USER_ID'), self::GetOption('API_KEY'));
        return $client->query($method, $params);
    }

    public static function UpdateSettings($values)
    {
        foreach($values as $k=>$v)
            $values[strtoupper($k)] = $v;

        //dirty-hack because have no time to make it good
        if(!isset($values['COMPONENT_IS_ACTIVE']))
            $values['COMPONENT_IS_ACTIVE'] = null;

        if(!isset($values['JS_TAB_IS_ACTIVE']))
            $values['JS_TAB_IS_ACTIVE'] = null;

        if(!isset($values['JS_TAB_CUSTOMIZE']))
            $values['JS_TAB_CUSTOMIZE'] = null;

        $component_settings = new GiftdComponentSettings(self::$MODULE_ID, new GenericHtmlBuilder());
        $tab_settings = new GiftdTabSettings(self::$MODULE_ID, new GenericHtmlBuilder());

        $component_settings->Update($values);
        $tab_settings->Update($values);

        $api_key = $values['API_KEY'];
        $user_id = $values['USER_ID'];

        if ($values['API_KEY'] != ($api_key_old = self::GetOption('API_KEY')) &&
            $values['USER_ID'] != ($user_id_old = self::GetOption('USER_ID'))) {
            static::handleUninstall($api_key_old, $user_id_old);
        }

        if (!empty($api_key) && !empty($user_id)) {
            $client = new GiftdClient($user_id, $api_key);
            $response = $client->query('bitrix/getData');
            if($response['type'] == 'data') {
                $siteData = static::_getSiteData();
                $client->query('bitrix/updateData', $siteData);

                $response = $client->query('bitrix/getData');

                $jsOptionsOld = self::GetOption('JS_TAB_OPTIONS');
                $partnerCodeOld = self::GetOption('PARTNER_CODE');

                self::SetOption('API_KEY', $api_key);
                self::SetOption('USER_ID', $user_id);

                self::SetOption('PARTNER_CODE', !empty($values['PARTNER_CODE']) ? $values['PARTNER_CODE'] : $response['data']['partner_code']);
                self::SetOption('PARTNER_TOKEN_PREFIX', !empty($values['PARTNER_TOKEN_PREFIX']) ?  $values['PARTNER_TOKEN_PREFIX'] : $response['data']['partner_token_prefix']);
                self::SetOption('DISABLE_BASKET_RULES', !empty($values['DISABLE_BASKET_RULES']) ?  1 : 0);

                if (!empty($jsOptionsOld)) {
                    self::SetOption(
                        'JS_TAB_OPTIONS',
                        str_replace($partnerCodeOld, $response['data']['partner_code'], $jsOptionsOld)
                    );
                }
            }
        } elseif (empty($api_key) && empty($user_id)) {
            self::SetOption('API_KEY', null);
            self::SetOption('USER_ID', null);
            self::SetOption('PARTNER_CODE', null);
            self::SetOption('PARTNER_TOKEN_PREFIX', null);
            self::SetOption('DISABLE_BASKET_RULES', null);
        }

        self::SetOption('SETTINGS', isset($values['SETTINGS']) ? $values['SETTINGS'] : null);
    }

    public static function getDefaultJsOptions()
    {
        return
'window.giftdOptions = {
    pid: "'.self::GetOption('PARTNER_CODE').'",
    tab: {
        enabled: '.(self::GetOption('JS_TAB_IS_ACTIVE') ? 'true' : 'false').',
        position: "'.(self::GetOption('JS_TAB_POSITION') ?: 'left') .'"
    }
};';
    }
    
    public static function handleOrderCheckStatusQuery()
    {
        if (isset($_REQUEST['giftd-check-order-status']) && $_REQUEST['giftd-check-order-status'] == self::getApiKey()) {

            if (isset($_REQUEST['giftd-order-id'])) {
                if (CModule::IncludeModule('sale')) {
                    $order = CSaleOrder::GetByID($_REQUEST['giftd-order-id']);
                    if ($order) {
                        $status = isset($order['STATUS_ID']) ? $order['STATUS_ID'] : null;
                        $totalAmount = isset($order['PRICE']) ? $order['PRICE'] : null;
                        if (isset($order['PRICE_DELIVERY'])) {
                            $totalAmount -= $order['PRICE_DELIVERY'];
                        }

                        $params = [
                            'order_id' => $order['ID'],
                            'status' => ($status === 'F' || $status === 'P') ? 'confirmed' : 'rejected',
                            'amount_total' => $totalAmount
                        ];

                        self::QueryApi('gift/updateOrderStatus', $params);
                    }
                }
            }
        } 
    }

    public static function getJSTabScript()
    {
        $KEY_CODE = 'JS_CODE_' . SITE_ID;

        if ((isset($_REQUEST['giftd-update-js']) && $_REQUEST['giftd-update-js'] == self::getApiKey()) || !($code = self::GetOption($KEY_CODE))) {
            try {
                $apiResponse = self::QueryApi('partner/getJs');
                $code = isset($apiResponse['data']['js']) ? $apiResponse['data']['js'] : "";

                self::SetOption($KEY_CODE, $code);
            } catch (Exception $e) {
                self::debug("Exception while update Giftd JS code: " . $e->getMessage());
                $code = "";
            }
        }
        return "<script>$code</script>";
    }
    
    function MakeModuleOptionsHtml()
    {
        $has_options_set = self::IsSetModuleSettings();
        $autoconfig = $has_options_set ? '' : '<a id="SIGN_IN" href="https://partner.giftd.ru/site/login?popup=1">'.GetMessage('SIGN_IN').'</a>';

        // making all fields visible
        // $style = $has_options_set ? '' : ' style="display:none;" ';
        $style = '';
        $disabled_fields = array();

        $html = '<tr class="heading"><td colspan="2">'. ($autoconfig ?: GetMessage('MODULE_API_SETTINGS')) .'</td></tr>';
        foreach(self::$API_OPTIONS as $key) {
            $disabled = in_array($key, $disabled_fields) ? ' disabled' : '';
            switch ($key) {
                case 'SETTINGS':
                    $html .= '<tr class="optional" '.$style.'>
                        <td class="adm-detail-content-cell-l" width="50%">'.GetMessage($key).' (JSON)</td>
                        <td class="adm-detail-content-cell-r" width="50%"><textarea name="SETTINGS" style="width: 300px; height: 200px;">' .GiftdHelper::GetOption($key).'</textarea></td>
                      </tr>';
                    break;
                case 'DISABLE_BASKET_RULES':
                    $html .= '<tr class="optional" '.$style.'>
                        <td class="adm-detail-content-cell-r" width="100%" colspan="2"><input type="hidden" value="" name="DISABLE_BASKET_RULES" /><input type=checkbox value="1" name="DISABLE_BASKET_RULES" id="DISABLE_BASKET_RULES" ' . (GiftdHelper::GetOption($key) ? 'checked' : '') . '/> <label for="DISABLE_BASKET_RULES">' . GetMessage($key) . '</label></td>
                      </tr>';
                    break;
                default:
                    $html .= '<tr class="optional" '.$style.'>
                        <td class="adm-detail-content-cell-l" width="50%">'.GetMessage($key).'</td>
                        <td class="adm-detail-content-cell-r" width="50%"><input type="text" name="'.$key.'" value="'.GiftdHelper::GetOption($key).'" '.$disabled.'></td>
                      </tr>';
            }

        }

        return $html;
    }

    function MakeComponentOptionsHtml()
    {
        $settings = new GiftdComponentSettings(self::$MODULE_ID, new GenericHtmlBuilder());
        return $settings->ToHtml();
    }

    function MakeTabOptionsHtml()
    {
        $settings = new GiftdTabSettings(self::$MODULE_ID, new GenericHtmlBuilder());
        return $settings->ToHtml();
    }

    function MakeGenericInputOptionFields($keys, $class)
    {
        $html = '';
        foreach($keys as $key) {
            $html .= '<tr class="optional '.$class.'">
                        <td class="adm-detail-content-cell-l" width="50%">'.GetMessage($key).'</td>
                        <td class="adm-detail-content-cell-r" width="50%"><input type="text" name="'.$key.'" value="'.GiftdHelper::GetOption($key).'"></td>
                      </tr>';
        }

        return $html;
    }
}

?>