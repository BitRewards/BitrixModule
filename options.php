<?
if (!$USER->CanDoOperation('edit_other_settings'))
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

$module_id = "giftd.coupon";
CModule::IncludeModule($module_id);
CModule::IncludeModule('fileman');

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["Update"] . $_POST["Apply"] . $_POST["RestoreDefaults"] <> '' && check_bitrix_sessid()) {

    if ($_POST["RestoreDefaults"] <> '') {
        COption::RemoveOption($module_id);
    } else {
        GiftdHelper::UpdateSettings($_POST);
    }
}
?>

<form method="post" name="giftd_settings"
      action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&lang=<?= urlencode(LANGUAGE_ID) ?>"
      enctype="multipart/form-data">
    <?
    $aTabs = array(
        array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
    );
    $tabControl = new CAdminTabControl("tabControl", $aTabs);

    $tabControl->Begin();
    $tabControl->BeginNextTab();

    echo GiftdHelper::MakeModuleOptionsHtml();

    if (GiftdHelper::IsSetModuleSettings()) {
        echo GiftdHelper::MakeComponentOptionsHtml();
        echo GiftdHelper::MakeTabOptionsHtml();
    }
    ?>

    <? $tabControl->Buttons(); ?>
    <input type="hidden" name="siteTabControl_active_tab"
           value="<?= htmlspecialcharsbx($_REQUEST["siteTabControl_active_tab"]) ?>">
    <? if ($_REQUEST["back_url_settings"] <> ''): ?>
        <input type="submit" name="Update" value="<?= GetMessage("MAIN_SAVE") ?>"
               title="<?= GetMessage("MAIN_OPT_SAVE_TITLE") ?>">
    <? endif ?>
    <input type="submit" name="Apply" value="<?= GetMessage("MAIN_OPT_APPLY") ?>"
           title="<?= GetMessage("MAIN_OPT_APPLY_TITLE") ?>">
    <? if ($_REQUEST["back_url_settings"] <> ''): ?>
        <input type="button" name="Cancel" value="<?= GetMessage("MAIN_OPT_CANCEL") ?>"
               title="<?= GetMessage("MAIN_OPT_CANCEL_TITLE") ?>"
               onclick="window.location='<? echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"])) ?>'">
        <input type="hidden" name="back_url_settings" value="<?= htmlspecialcharsbx($_REQUEST["back_url_settings"]) ?>">
    <? endif ?>
    <input type="submit" name="RestoreDefaults" title="<? echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>"
           onclick="return confirm('<? echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING")) ?>')"
           value="<? echo GetMessage("MAIN_RESTORE_DEFAULTS") ?>">
    <?= bitrix_sessid_post(); ?>
    <? $tabControl->End(); ?>
</form>

<script>
    var Giftd = {
        popup: null,
        openWindow: function(options) {
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
            Giftd.popup = window.open(options.url, 'giftd_auth_' + new Date().getTime(), features);
        },
        updateApiKey: function(user_id, api_key) {
            $('input[name=API_KEY]').val(api_key);
            $('input[name=USER_ID]').val(user_id);
            $('form[name=giftd_settings] input[name=Apply]').click();
        },
        updateComponentSettings: function() {
            if ($('input[name=COMPONENT_IS_ACTIVE]').prop('checked'))
                $('tr.component_field').show();
            else
                $('tr.component_field').hide();
        },
        updateComponentTemplateSettings: function(){
            var items = $('tr.template_field');
            if ($('select[name=COMPONENT_TEMPLATE]').val() != 'PHP') {
                $('tr.template_field').show();
            } else {
                $('tr.template_field').hide();
            }
        },
        updateTabSettings: function(){
            var checked = $('input[name=JS_TAB_IS_ACTIVE').is(':checked');
            $('tr.tab_field input').prop('disabled', !checked);
            $('tr.tab_field').toggle(checked);
            $('tr.tab_disabled_field').toggle(!checked);
        },
        updateTabCustomizeSettings: function(){
            var checked = $('input[name=JS_TAB_CUSTOMIZE').is(':checked');
            $('tr.tab_customize_field input, tr.tab_customize_field textarea').prop('disabled', !checked);
            $('tr.tab_customize_field').toggle(checked);
        },
        validateJsTabOptions: function(){
            var $options = $('[name=JS_TAB_OPTIONS]');
            if (!$options.is(':visible')) {
                return true;
            }
            var code = $options.val();
            var result = true;
            try {
                eval(code);
            } catch (e) {
                result = false;
            }
            if (!result) {
                alert("<?php echo GetMessage('JS_TAB_OPTIONS_SYNTAX_ERROR') ?>");
                return false;
            }
            if (!window.giftdOptions || window.giftdOptions.pid != $('[name=PARTNER_CODE]').val()) {
                alert("<?php echo GetMessage('JS_TAB_OPTIONS_ERROR') ?>");
                return false;
            }
            return true;
        }
    };

    $(function () {
        $(window).on('message', function (message) {
            var rawMessage = message.data || message.originalEvent.data;
            if (typeof rawMessage == 'string' && rawMessage.indexOf("giftd/auth") === 0) {
                var message = JSON.parse(rawMessage.split("~", 2)[1]);
                switch (message.type) {
                    case 'error':
                        alert(message.data);
                        break;
                    case 'data':
                        Giftd.updateApiKey(message.data.user_id, message.data.api_key);
                        break;
                    default:
                        break;
                }
            }
            Giftd.popup.close();
        });

        $('#SIGN_IN').click(function () {
            Giftd.openWindow({
                width: 520,
                height: 453,
                url: this.href
            });
            return false;
        });

        $('input[name=COMPONENT_IS_ACTIVE').click(Giftd.updateComponentSettings);
        $('select[name=COMPONENT_TEMPLATE]').change(Giftd.updateComponentTemplateSettings);
        $('input[name=JS_TAB_IS_ACTIVE').change(Giftd.updateTabSettings);
        $('input[name=JS_TAB_CUSTOMIZE').change(Giftd.updateTabCustomizeSettings);

        Giftd.updateComponentTemplateSettings();
        Giftd.updateTabSettings();
        Giftd.updateComponentSettings();
        Giftd.updateTabCustomizeSettings();

        $('[name=Apply]').click(function(){
            if (!Giftd.validateJsTabOptions()) {
                document.location.reload();
                return false;
            }
        });
    });


</script>

<script src="//yastatic.net/jquery/2.1.1/jquery.min.js"></script>

