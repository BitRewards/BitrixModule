<?
if(!$USER->CanDoOperation('edit_other_settings'))
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

$module_id = "giftd.coupon";
CModule::IncludeModule($module_id);
CModule::IncludeModule('fileman');

$APPLICATION->AddHeadScript('https://yandex.st/jquery/2.0.3/jquery.min.js');

if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["Update"].$_POST["Apply"].$_POST["RestoreDefaults"] <> ''  && check_bitrix_sessid())
{

    if($_POST["RestoreDefaults"] <> '')
    {
        COption::RemoveOption($module_id);
    }
    else
    {
        GiftdHelper::UpdateSettings($_POST);
    }
}
?>

<form method="post" name="giftd_settings" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($module_id)?>&amp;lang=<?=urlencode(LANGUAGE_ID)?>" enctype="multipart/form-data">
    <?
    $aTabs = array(
        array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
    );
    $tabControl = new CAdminTabControl("tabControl", $aTabs);

    $tabControl->Begin();
    $tabControl->BeginNextTab();

    echo GiftdHelper::MakeModuleOptionsHtml();

    if(GiftdHelper::IsSetModuleSettings())
    {
        echo GiftdHelper::MakeComponentOptionsHtml();
        echo GiftdHelper::MakePanelOptionsHtml();
    }
    ?>

    <?$tabControl->Buttons();?>
    <input type="hidden" name="siteTabControl_active_tab" value="<?=htmlspecialcharsbx($_REQUEST["siteTabControl_active_tab"])?>">
    <?if($_REQUEST["back_url_settings"] <> ''):?>
        <input type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>">
    <?endif?>
    <input type="submit" name="Apply" value="<?=GetMessage("MAIN_OPT_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
    <?if($_REQUEST["back_url_settings"] <> ''):?>
        <input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
        <input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
    <?endif?>
    <input type="submit" name="RestoreDefaults" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" onclick="return confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
    <?=bitrix_sessid_post();?>
    <?$tabControl->End();?>
</form>

<script language="JavaScript">
    var popup = null;
    function openWindow(options) {
        var
            screenX = typeof window.screenX != 'undefined' ? window.screenX : window.screenLeft,
            screenY = typeof window.screenY != 'undefined' ? window.screenY : window.screenTop,
            outerWidth = typeof window.outerWidth != 'undefined' ? window.outerWidth : document.body.clientWidth,
            outerHeight = typeof window.outerHeight != 'undefined' ? window.outerHeight : (document.body.clientHeight - 22),
            width = options.width,
            height = options.height,
            left = parseInt(screenX + ((outerWidth - width) / 2), 10),
            top = parseInt(screenY + ((outerHeight - height) / 2.5), 10),
            features = (
                'width=' + width +
                    ',height=' + height +
                    ',left=' + left +
                    ',top=' + top
                );
        popup = window.open(options.url, 'giftd_auth_' + new Date().getTime(), features);
    }

    function update_api_key(user_id, api_key)
    {
        $('input[name=API_KEY]').val(api_key);
        $('input[name=USER_ID]').val(user_id);
        $('form[name=giftd_settings] input[name=Apply]').click();
    }

    $(function(){
        $(window).on('message', function (message) {
            var rawMessage = message.data || message.originalEvent.data;
            console.log(rawMessage);
            if (typeof rawMessage == 'string' && rawMessage.indexOf("giftd/auth") === 0) {
                var message = JSON.parse(rawMessage.split("~", 2)[1]);
                console.log(message);
                switch (message.type) {
                    case 'error':
                        alert(message.data);
                        break;
                    case 'data':
                        update_api_key(message.data.user_id, message.data.api_key);
                        break;
                    default:
                        break;
                }
            }
            popup.close();
        });
    });

    $(function(){
        $('#SIGN_IN').click(function(){
            openWindow({
                width: 520,
                height: 453,
                url: this.href
            });
            return false;
        });
    });

    function UpdateComponentSettings()
    {
        if($('input[name=COMPONENT_IS_ACTIVE]').prop('checked'))
            $('tr.component_field').show();
        else
            $('tr.component_field').hide();
    }

    function UpdateComponentTemplateSettings()
    {
        var items = $('tr.template_field');
        if($('select[name=COMPONENT_TEMPLATE]').val() != 'PHP')
            $('tr.template_field').show();
        else
            $('tr.template_field').hide();
    }

    function UpdatePanelSettings()
    {
        if($('input[name=JS_PANEL_IS_ACTIVE').prop('checked')) {
            $('tr.panel_field').show();
            $('tr.panel_field').find('input').prop('disabled', false);
        } else {
            $('tr.panel_field').hide();
            $('tr.panel_field').find('input').prop('disabled', true);
        }
    }



    $(function(){

        $('input[name=COMPONENT_IS_ACTIVE').click(UpdateComponentSettings);
        $('select[name=COMPONENT_TEMPLATE]').change(UpdateComponentTemplateSettings);
        $('input[name=JS_PANEL_IS_ACTIVE').click(UpdatePanelSettings);

        UpdateComponentTemplateSettings();
        UpdatePanelSettings();
        UpdateComponentSettings();

        /*
        if($('[name=COMPONENT_IS_ACTIVE]').prop('checked') == false)
            $('tr.component_field').hide();

        if($('select[name=COMPONENT_TEMPLATE]').val() == 'PHP')
            $('tr.template_field').hide();

        if($('[name=JS_PANEL_IS_ACTIVE]').prop('checked') == false)
            $('tr.panel_field').hide();
        */
    });

</script>