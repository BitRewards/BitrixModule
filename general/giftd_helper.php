<?php
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/giftd.coupon/install/version.php');
IncludeModuleLangFile(__FILE__);

class GiftdHelper
{
    static public $MODULE_ID = 'giftd.coupon';

    static public $API_OPTIONS = array('API_KEY', 'USER_ID', 'PARTNER_CODE', 'PARTNER_TOKEN_PREFIX', 'SETTINGS');
    static public $COMPONENT_OPTIONS = array('COMPONENT_IS_ACTIVE', 'COMPONENT_TEMPLATE', 'COMPONENT_TEMPLATE_JS_COUPON_FIELD_ID', 'COMPONENT_TEMPLATE_JS_CALLBACK');
    static public $TAB_OPTIONS = array('JS_TAB_IS_ACTIVE', 'JS_TAB_POSITION', 'JS_TAB_CUSTOMIZE', 'JS_TAB_OPTIONS');

    private static $_optionsCache = array();

    function CheckPatchOnBeforeProlog()
    {
        $cache = new CPHPCache();
        if (!$cache->InitCache(60, $cacheId = 'giftd-discount-patch-check', '/')) {
            $cache->StartDataCache(60, $cacheId, '/');
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

    public static function InjectJSPanelScriptOnBeforeProlog()
    {
        self::InjectJSTabScriptOnBeforeProlog();
    }

    public static function InjectJSTabScriptOnBeforeProlog()
    {
        global $APPLICATION;

        if (self::IsSetModuleSettings() &&
            (!defined('ADMIN_SECTION') || !ADMIN_SECTION) &&
            (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/admin/') === false)
            ) {
            $APPLICATION->AddHeadString(self::getJSTabScript());

            if (self::GetOption("REPLACE_TOP_WITH_PARENT")) {
                define('GIFTD_REPLACE_TOP_WITH_PARENT', true);
                ob_start();
            }
        }
    }

    public static function ReplaceTopWithParentOnAfterEpilog()
    {
        if (defined('GIFTD_REPLACE_TOP_WITH_PARENT')) {
            $content = ob_get_clean();
            $oneliner = '((function(){ try {return window.parent.location.href ? window.parent : window;} catch (e) {return window;}})())';
/*
            echo preg_replace(
                "/([^\.a-z0-9A-Z])(window\.)?(top|parent)\s*([\"\'\.\[])(?![\"']?location)/uis",
                '$1((function(){ try {return !$3.location ? $3 : (window.parent.location.href ? window.parent : window);} catch (e) {return window;}})())$4',
                $content
            );

            return;

*/

            $onelinerLower = "$oneliner.bx";
            $onelinerUpper = "$oneliner.BX";

            echo str_replace(
                array("window.top.bx", "window.parent.bx", "top.bx", "parent.bx", "window.top.BX", "window.parent.BX", "top.BX", "parent.BX", "window.top.rsasec", "top.rsasec", "top.$", "window.top.$"),
                array($onelinerLower, $onelinerLower, $onelinerLower, $onelinerLower, $onelinerUpper, $onelinerUpper, $onelinerUpper, $onelinerUpper, "$oneliner.rsasec", "$oneliner.rsasec", "$oneliner.$", "$oneliner.$"),
                $content
            );
        }
    }

    function MakeSettingArray($name, $type, $opt = '', $disabled = '')
    {
        return array($name, GetMessage($name), self::GetOption($name), array($type, $opt), $disabled);
    }

    public static function GetOption($key)
    {
        if ($key != 'SETTINGS' && SITE_ID && (!defined('ADMIN_SECTION') || !ADMIN_SECTION)) {
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

            if ($api_key_old && $user_id_old) {
                static::handleUninstall($api_key_old, $user_id_old);
            }

            if (!empty($api_key) && !empty($user_id)) {
                $client = new GiftdClient($values['USER_ID'], $values['API_KEY']);
                $response = $client->query('bitrix/getData');
                if($response['type'] == 'data') {
                    $siteData = static::_getSiteData();
                    $client->query('bitrix/updateData', $siteData);

                    $jsOptionsOld = self::GetOption('JS_TAB_OPTIONS');
                    $partnerCodeOld = self::GetOption('PARTNER_CODE');

                    self::SetOption('API_KEY', $values['API_KEY']);
                    self::SetOption('USER_ID', $values['USER_ID']);
                    self::SetOption('PARTNER_CODE', $response['data']['partner_code']);
                    self::SetOption('PARTNER_TOKEN_PREFIX', $response['data']['partner_token_prefix']);

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
            }
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

    public static function getJSTabScript()
    {
        $pid = self::GetOption('PARTNER_CODE');

        return <<<HTML
<script>
setTimeout(function(){
    var el = document.createElement("script"); el.id = "giftd-script"; el.async = true;
    el.src = "http://u.giftd.ru/widgets/js/v2?pid=$pid"; el.crossOrigin = "anonymous";
    document.getElementsByTagName("head")[0].appendChild(el);
}, 0);
</script>
HTML;
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