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

    function UpdateSettings($values)
    {
        global $USER, $arModuleVersion;

        foreach($values as $k=>$v)
            $values[strtoupper($k)] = $v;

        if(!isset($values['COMPONENT_IS_ACTIVE']))
            $values['COMPONENT_IS_ACTIVE'] = 'N';

        if(!isset($values['JS_PANEL_IS_ACTIVE']))
            $values['JS_PANEL_IS_ACTIVE'] = 'N';

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

                $client->query('bitrix/updateData', [
                    'email' => COption::GetOptionString("main", "email_from"),
                    'phone' => '',
                    'name' => $USER->GetFullName(),
                    'url' => 'http://'.$_SERVER['SERVER_NAME '].'/',
                    'bitrix_module_version' => $arModuleVersion["VERSION"]
                ]);
            }
        }

        $allSettings = array_merge(self::$COMPONENT_OPTIONS, self::$PANEL_OPTIONS);
        foreach($allSettings as $key) {
            if(isset($values[$key]))
                COption::SetOptionString(self::$MODULE_ID, $key, $values[$key]);
        }


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
        $is_options_set = self::GetOption('COMPONENT_IS_ACTIVE')=='Y';
        $selected = $is_options_set ? 'checked="checked"' : '';
        $style = $is_options_set ? '' : ' style="display:none;"';

        $html = '<tr class="heading optional"><td colspan="2">'.GetMessage('COMPONENT_IS_ACTIVE').' <input type="checkbox" name="COMPONENT_IS_ACTIVE" value="Y" '.$selected.'></td></tr>';

        $html.= '<tr class="optional component_field" '.$style.'><td>'.GetMessage('COMPONENT_TEMPLATE').'</td>';
        $html.= '<td><select name="COMPONENT_TEMPLATE">';
        foreach(array('PHP'=>GetMessage('COMPONENT_TEMPLATE_TYPE_PHP'), 'PHPJS'=>GetMessage('COMPONENT_TEMPLATE_TYPE_PHPJS'), 'HTML'=>GetMessage('COMPONENT_TEMPLATE_TYPE_HTML')) as $value=>$title) {
            $html .= ' <option value="'.$value.'" '.(self::GetOption('COMPONENT_TEMPLATE') == $value ? 'selected' : '').'>'.$title.'</option>';
        }
        $html.= '</select></td></tr>';

        $skip = array('COMPONENT_IS_ACTIVE', 'COMPONENT_TEMPLATE');
        $html.= self::MakeGenericInputOptionFields(array_diff(self::$COMPONENT_OPTIONS, $skip), 'template_field');

        return $html;
    }

    function MakePanelOptionsHtml()
    {
        $html = '<tr class="heading optional"><td colspan="2">'.GetMessage('JS_PANEL_IS_ACTIVE').' <input type="checkbox" name="JS_PANEL_IS_ACTIVE" value="Y" switch="panel_field"'.(self::GetOption('JS_PANEL_IS_ACTIVE')=='Y' ? 'checked="checked"' : '').'></td></tr>';
        $html.= '<tr class="optional panel_field"><td>'.GetMessage('JS_TAB_POSITION').'</td>';
        $html.= '<td>';
        foreach(array('top'=>'Top', 'left'=>'Left', 'bottom'=>'Bottom') as $value=>$title) {
            $html .= ' <input type="radio" name="JS_TAB_POSITION" value="'.$value.'" '.(self::GetOption('JS_TAB_POSITION') == $value ? 'checked="checked"' : '').'>'.$title;
        }
        $html.= '</td></tr>';
        $html.= '<tr class="optional panel_field"><td></td><td><img src="'.BX_ROOT.'/modules/giftd.coupon/img/embedded_tab.png"</td></tr>';

        $skip = array('JS_PANEL_IS_ACTIVE', 'JS_TAB_POSITION');
        $html.= self::MakeGenericInputOptionFields(array_diff(self::$PANEL_OPTIONS, $skip), 'panel_field');

        return $html;
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