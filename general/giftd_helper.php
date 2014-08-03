<?php
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/giftd.coupon/install/version.php');
IncludeModuleLangFile(__FILE__);

class GiftdHelper
{
    static public $MODULE_ID = 'giftd.coupon';

    static public $API_OPTIONS = array('API_KEY', 'USER_ID', 'PARTNER_CODE', 'PARTNER_TOKEN_PREFIX');
    static public $COMPONENT_OPTIONS = array('COMPONENT_IS_ACTIVE', 'COMPONENT_TEMPLATE', 'COMPONENT_TEMPLATE_JS_COUPON_FIELD_ID', 'COMPONENT_TEMPLATE_JS_CALLBACK');
    static public $PANEL_OPTIONS = array('JS_PANEL_IS_ACTIVE', 'JS_TAB_POSITION', 'JS_TAB_PANEL_BG_COLOR', 'JS_TAB_PANEL_BG_IMAGE', 'JS_TAB_PANEL_DECOR_TOP', 'JS_TAB_PANEL_DECOR_BOTTOM', 'JS_PANEL_TEXT_COLOR', 'JS_PANEL_DESCRIPTION_COLOR', 'JS_PANEL_DESCRIPTION_ICON', 'JS_CONTENT_BG_IMAGE', 'JS_CONTENT_COLOR', 'JS_CONTENT_TITLE_COLOR');

    function CheckPatchOnBeforeProlog()
    {
        $patch_source = $_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/'.self::$MODULE_ID.'/general/discount_coupon.php';
        $patch_target = $_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/catalog/general/discount_coupon.php';

        $is_patched = strstr(file_get_contents($patch_target), 'giftdpatched');
        if(!$is_patched && self::IsSetModuleSettings())
        {
            CopyDirFiles($patch_target, $_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/catalog/general/base_discount_coupon.php', true);
            CopyDirFiles($patch_source, $patch_target, true);
        }
    }
    
    function InjectJSPanelScriptOnBeforeProlog()
    {
        global $APPLICATION;

        if (self::IsSetModuleSettings() && strpos($_SERVER['REQUEST_URI'], BX_ROOT.'/admin') === false)
            $APPLICATION->AddHeadString(self::MakeJSPanelScript());
    }

    function MakeSettingArray($name, $type, $opt = '', $disabled = '')
    {
        return array($name, GetMessage($name), self::GetOption($name), array($type, $opt), $disabled);
    }

    function GetOption($key)
    {
        return COption::GetOptionString(self::$MODULE_ID, $key);
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
        return self::IsSetSettings(self::$API_OPTIONS);
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

    public function handleUninstall($api_key, $user_id)
    {
        $client = new GiftdClient($api_key, $user_id);
        try {
            $client->query('bitrix/uninstall', $this->_getSiteData());
        } catch (Exception $e) {
            try {
                $client = new GiftdClient(null, null);
                $client->query('bitrix/uninstall', $this->_getSiteData());
            } catch (Exception $e) {
                $from = COption::GetOptionString("main", "email_from");
                $headers = $from ? "From: $from" : null;
                mail("partner@giftd.ru", "Ошибка при деинсталляция модуля Giftd для Битрикса", $e->__toString(), $headers);
            }
        }
    }

    private function _getSiteData()
    {
        global $USER, $arModuleVersion;

        $schema = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http';
        $host = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);

        $siteData = CSite::GetByID(SITE_ID)->Fetch();
        $userData = isset($USER) ? $USER->GetByID($USER->GetId())->Fetch() : null;

        return array(
            'email' => isset($USER) ? $USER->GetEmail() ?: COption::GetOptionString("main", "email_from") : null,
            'phone' => $userData ? (isset($userData['PERSONAL_PHONE']) ? $userData['PERSONAL_PHONE'] : $userData['WORK_PHONE']) : null,
            'name' => isset($USER) ? $USER->GetFullName() : null,
            'url' => $schema . '://'. $host . '/',
            'title' => isset($siteData['SITE_NAME']) ? $siteData['SITE_NAME'] : null,
            'bitrix_module_version' => isset($arModuleVersion) ? $arModuleVersion["VERSION"] : null,
        );
    }

    function UpdateSettings($values)
    {
        foreach($values as $k=>$v)
            $values[strtoupper($k)] = $v;

        //dirty-hack because have no time to make it good
        if(!isset($values['COMPONENT_IS_ACTIVE']))
            $values['COMPONENT_IS_ACTIVE'] = 'N';

        if(!isset($values['JS_PANEL_IS_ACTIVE']))
            $values['JS_PANEL_IS_ACTIVE'] = 'N';

        if(!isset($values['JS_PANEL_DECOR_IS_ACTIVE']))
            $values['JS_PANEL_DECOR_IS_ACTIVE'] = 'N';

        $component_settings = new GiftdComponentSettings(self::$MODULE_ID, new GenericHtmlBuilder());
        $panel_settings = new GiftdPanelSettings(self::$MODULE_ID, new GenericHtmlBuilder());

        $component_settings->Update($values);
        $panel_settings->Update($values);
        /*
        if($dst = self::UpdateFromFile('JS_TAB_PANEL_BG_IMAGE', '_FILE'))
             $values['JS_TAB_PANEL_BG_IMAGE'] = $dst;

        if($dst = self::UpdateFromFile('JS_CONTENT_BG_IMAGE', '_FILE'))
             $values['JS_CONTENT_BG_IMAGE'] = $dst;
        */
        $api_key = $values['API_KEY'];
        $user_id = $values['USER_ID'];
        if ($values['API_KEY'] != ($api_key_old = self::GetOption('API_KEY')) &&
            $values['USER_ID'] != ($user_id_old = self::GetOption('USER_ID'))) {
            if (!empty($api_key) && !empty($user_id)) {
                $client = new GiftdClient($values['USER_ID'], $values['API_KEY']);
                $response = $client->query('bitrix/getData');
                if($response['type'] == 'data') {
                    $siteData = $this->_getSiteData();
                    $client->query('bitrix/updateData', $siteData);

                    COption::SetOptionString(self::$MODULE_ID, 'API_KEY', $values['API_KEY']);
                    COption::SetOptionString(self::$MODULE_ID, 'USER_ID', $values['USER_ID']);
                    COption::SetOptionString(self::$MODULE_ID, 'PARTNER_CODE', $response['data']['partner_code']);
                    COption::SetOptionString(self::$MODULE_ID, 'PARTNER_TOKEN_PREFIX', $response['data']['partner_token_prefix']);
                }
            } elseif (empty($api_key) && empty($user_id)) {
                $this->handleUninstall($api_key_old, $user_id_old);
            }
        }

        /*
        $allSettings = array_merge(self::$COMPONENT_OPTIONS, self::$PANEL_OPTIONS);
        foreach($allSettings as $key) {
            if(isset($values[$key]))
                COption::SetOptionString(self::$MODULE_ID, $key, $values[$key]);
        }
        */

    }

    function MakeJSPanelScript()
    {
        $image_path = self::GetOption('JS_TAB_PANEL_BG_IMAGE');
        if(strlen($image_path) > 0 && self::GetOption('JA_TAB_PANEL_GB_IMAGE_BASE64') == 'Y')
        {
            $image_path = 'data:'.self::GetOption('JA_TAB_PANEL_GB_IMAGE_TYPE').';base64,'.base64_encode(file_get_contents($image_path));
        }

        $script =
            '<script type="text/javascript">
                window.giftdOptions = {
                    pid: "'.self::GetOption('PARTNER_CODE').'",
                    tab: {
                        enabled: '.(self::GetOption('JS_PANEL_IS_ACTIVE') ? 'true' : 'false').',
                        position: "'.self::GetOption('JS_TAB_POSITION').'",
                        panelBg: {
                             color: "'.self::GetOption('JS_TAB_PANEL_BG_COLOR').'",
                             image: "'.$image_path.'"
                        },
                        panelDecor: {
                            top: "'.self::GetOption('JS_TAB_PANEL_DECOR_TOP').'",
                            bottom: "'.self::GetOption('JS_TAB_PANEL_DECOR_BOTTOM').'"
                        },
                        panelTextColor: "'.self::GetOption('JS_PANEL_TEXT_COLOR').'",
                        panelDescriptionColor: "'.self::GetOption('JS_PANEL_DESCRIPTION_COLOR').'",
                        panelDescriptionIcon: "'.self::GetOption('JS_PANEL_DESCRIPTION_ICON').'",
                        contentBgImage: "'.self::GetOption('JS_CONTENT_BG_IMAGE').'",
                        contentColor: "'.self::GetOption('JS_CONTENT_COLOR').'",
                        contentTitleColor: "'.self::GetOption('JS_CONTENT_TITLE_COLOR').'"
                    }
                };

                window.onload = 
                    function(){
                        var s = (window.giftdOptions.tab && window.giftdOptions.tab.enabled) ? "giftd.js" : "giftd_no_tab.js";
                        var el = document.createElement("script");
                        el.id = "giftd-script";
                        el.async = true;
                        el.src = "https://static.giftd.ru/embedded/" + s";
                        document.getElementsByTagName("head")[0].appendChild(el);
                    };
            </script>';

        return $script;
    }

    function MakeModuleOptionsHtml()
    {
        $has_options_set = self::IsSetModuleSettings();
        $autoconfig = $has_options_set ? '' : '(<a id="SIGN_IN" href="https://partner.giftd.ru/site/login?popup=1">'.GetMessage('SIGN_IN').')';
        $style = $has_options_set ? '' : ' style="display:none;" ';
        $disabled = array('PARTNER_CODE', 'PARTNER_TOKEN_PREFIX');

        $html = '<tr class="heading"><td colspan="2">'.GetMessage('MODULE_API_SETTINGS').' '.$autoconfig.'</td></tr>';
        foreach(self::$API_OPTIONS as $key) {
            $disabled = in_array($key, $disabled) ? ' disabled' : '';
            $html .= '<tr class="optional" '.$style.'>
                        <td class="adm-detail-content-cell-l" width="50%">'.GetMessage($key).'</td>
                        <td class="adm-detail-content-cell-r" width="50%"><input type="text" name="'.$key.'" value="'.GiftdHelper::GetOption($key).'" '.$disabled.'></td>
                      </tr>';
        }

        return $html;
    }

    function MakeComponentOptionsHtml()
    {
        $settings = new GiftdComponentSettings(self::$MODULE_ID, new GenericHtmlBuilder());
        return $settings->ToHtml();
    }

    function MakePanelOptionsHtml()
    {
        $settings = new GiftdPanelSettings(self::$MODULE_ID, new GenericHtmlBuilder());
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