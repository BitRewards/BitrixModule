<?php
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/giftd.coupon/install/version.php');
IncludeModuleLangFile(__FILE__);

class GiftdHelper
{
    static public $MODULE_ID = 'giftd.coupon';

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
        $keys = array();
        foreach(self::GetModuleSettings() as $arr)
            $keys[] = $arr[0];

        return self::IsSetSettings($keys);
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

    function UpdateSettings($values)
    {
        global $USER, $arModuleVersion;

        foreach($values as $k=>$v)
            $values[strtoupper($k)] = $v;

        if( $values['API_KEY'] <> '' && $values['API_KEY'] != self::GetOption('API_KEY') &&
            $values['USER_ID'] <> '' && $values['API_KEY'] != self::GetOption('USER_ID'))
        {
            $client = new GiftdClient($values['USER_ID'], $values['API_KEY']);
            $response = $client->query('bitrix/getData');
            if($response['type'] == 'data')
            {
                COption::SetOptionString(self::$MODULE_ID, 'API_KEY', $values['API_KEY']);
                COption::SetOptionString(self::$MODULE_ID, 'USER_ID', $values['USER_ID']);
                COption::SetOptionString(self::$MODULE_ID, 'PARTNER_CODE', $response['data']['partner_code']);
                COption::SetOptionString(self::$MODULE_ID, 'PARTNER_TOKEN_PREFIX', $response['data']['partner_token_prefix']);

                $client->query('bitrix/updateData', array(
                    'email' => COption::GetOptionString("main", "email_from"),
                    'phone' => '',
                    'name' => $USER->GetFullName(),
                    'url' => 'http://'.$_SERVER['SERVER_NAME '].'/',
                    'bitrix_module_version' => $arModuleVersion["VERSION"]
                ));
            }
        }

        $allSettings = array_merge(self::GetComponentSettings(), self::GetJSPanelSettings());
        foreach($allSettings as $arr) {
            $key = $arr[0];
            if(isset($values[$key]))
                COption::SetOptionString(self::$MODULE_ID, $key, $values[$key]);
        }
    }

    function GetModuleSettings()
    {
        $options =
            array(
                self::MakeSettingArray("API_KEY", "text", 45),
                self::MakeSettingArray("USER_ID", "text", 45),
                self::MakeSettingArray("PARTNER_CODE", "text", 45, 'Y'),
                self::MakeSettingArray("PARTNER_TOKEN_PREFIX", "text", 45, 'Y')
            );

        return $options;
    }

    function GetComponentSettings()
    {
        $disabled = self::GetOption('COMPONENT_IS_ACTIVE') == 'Y' ? '' : 'Y';
        $options =
            array(
                self::MakeSettingArray("COMPONENT_IS_ACTIVE", "checkbox"),
                self::MakeSettingArray("COMPONENT_TEMPLATE", "selectbox", array(''=>GetMessage('COMPONENT_TEMPLATE_TYPE_NO'), 'JS'=>GetMessage('COMPONENT_TEMPLATE_TYPE_AJAX'), 'INPUT'=>GetMessage('COMPONENT_TEMPLATE_TYPE_INPUT')), $disabled),
                self::MakeSettingArray("COMPONENT_TEMPLATE_JS_COUPON_FIELD_ID", "text", 45, $disabled),
                self::MakeSettingArray("COMPONENT_TEMPLATE_JS_CALLBACK", "text", 45, $disabled)
            );

        return $options;
    }

    function GetJSPanelSettings()
    {
        $disabled = self::GetOption('JS_PANEL_IS_ACTIVE') == 'Y' ? '' : 'Y';
        $options =
            array(
                self::MakeSettingArray("JS_PANEL_IS_ACTIVE", "checkbox"),
                self::MakeSettingArray("JS_TAB_POSITION", "selectbox", array('top'=>'Top', 'left'=>'Left', 'bottom'=>'Bottom'), $disabled),
                self::MakeSettingArray("JS_TAB_PANEL_BG_COLOR", "text", 45, $disabled),
                self::MakeSettingArray("JS_TAB_PANEL_BG_IMAGE", "text", 45, $disabled),
                self::MakeSettingArray("JS_TAB_PANEL_DECOR_TOP", "text", 45, $disabled),
                self::MakeSettingArray("JS_TAB_PANEL_DECOR_BOTTOM", "text", 45, $disabled),
                self::MakeSettingArray("JS_PANEL_TEXT_COLOR", "text", 45, $disabled),
                self::MakeSettingArray("JS_PANEL_DESCRIPTION_COLOR", "text", 45, $disabled),
                self::MakeSettingArray("JS_PANEL_DESCRIPTION_ICON", "text", 45, $disabled),
                self::MakeSettingArray("JS_CONTENT_BG_IMAGE", "text", 45, $disabled),
                self::MakeSettingArray("JS_CONTENT_COLOR", "text", 45, $disabled),
                self::MakeSettingArray("JS_CONTENT_TITLE_COLOR", "text", 45, $disabled),
            );

        return $options;
    }

    function MakeJSPanelScript()
    {
        $script =
            '<script type="text/javascript">
                window.giftdOptions = {
                    pid: "'.self::GetOption('PARTNER_CODE').'",
                    tab: {
                        enabled: '.(self::GetOption('JS_PANEL_IS_ACTIVE') ? 'true' : 'false').',
                        position: "'.self::GetOption('JS_TAB_POSITION').'",
                        panelBg: {
                             color: "'.self::GetOption('JS_TAB_PANEL_BG_COLOR').'",
                             image: "'.self::GetOption('JA_TAB_PANEL_GB_IMAGE').'"
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
                        el.src = "https://static.giftd.ru/embedded/" + s + "?rev=1990053171";
                        document.getElementsByTagName("head")[0].appendChild(el);
                    };
            </script>';

        return $script;
    }
}
?>