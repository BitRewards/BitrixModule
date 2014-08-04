<?
$MESS ['SAVE'] = "Сохранить";
$MESS ['RESET'] = "Сбросить";

$MESS ['MODULE_API_SETTINGS'] = "Доступ к API";
$MESS ['SIGN_IN'] = "Получить параметры для доступа к API Giftd";
$MESS ['API_KEY'] = "Ключ API Giftd (api_key)";
$MESS ['USER_ID'] = "ID пользователя Giftd (user_id)";
$MESS ['PARTNER_CODE'] = "Код партнера Giftd";
$MESS ['PARTNER_TOKEN_PREFIX'] = "Префикс кодов подарочных карт";


$MESS ['COMPONENT_IS_ACTIVE'] = "Использовать компонент проверки кодов подарочных карт";
$MESS ["COMPONENT_TEMPLATE"] = "Шаблон компонента";
$MESS ["COMPONENT_TEMPLATE_JS_COUPON_FIELD_ID"] = "ID элемента, в который вводятся коды подарочных карт";
$MESS ["COMPONENT_TEMPLATE_JS_CALLBACK"] = "JS функция, вызываемая после обработки кода подарочной карты";

$MESS ["COMPONENT_TEMPLATE_HELP"]  = '<p><b>Совет</b>: если на экране просмотра корзины уже есть поле для ввода скидочного кода, то скорее всего вам подойдет самый простой вариант — «<b>Серверный обработчик</b>»</p>';
$MESS ["COMPONENT_TEMPLATE_HELP"] .= '<p>1. Используя шаблон «<b>Серверный обработчик</b>», вы целиком доверяете проверку кодов штатным механизмам Битрикса. <ul><li>Если же вам нужно самостоятельно проверять код подарочной карты — выполняйте POST-запрос в формате JSON <code>{coupon: "secret_gift_token"}</code> серверному обработчику по адресу <code style="white-space: nowrap;">/bitrix/components/giftd/coupon_input/ajax.php</code></li></ul></p>';
$MESS ["COMPONENT_TEMPLATE_HELP"] .= '<p>2. При использовании компонента «<b>giftd:coupon_input</b>» с шаблоном «<b>Серверный обработчик и AJAX-валидатор</b>", на странице, где был размещен компонент, будет автоматически встроен JS-код проверки кодов Giftd. Код будет использовать вводимые ниже ID элемента и JS-функцию.</p>';
$MESS ["COMPONENT_TEMPLATE_HELP"] .= '<p>3. При использовании компонента «<b>giftd:coupon_input</b>» с шаблоном «<b>Серверный обработчик, AJAX-валидатор и HTML-форма</b>", на странице, где был размещен компонент, будет автоматически встроены скрипт проверки кодов Giftd и поле для ввода кодов вида <code style="white-space: nowrap;">&lt;input type="text" name="coupon"&gt;</code></p>';
$MESS ["COMPONENT_TEMPLATE_JS_COUPON_FIELD_ID_HELP"] = "Укажите параметр id вашего поля для ввода скидочных кодов, при изменении которого скрипт компонента <b>giftd:coupon_input</b> отправит запрос на проверку кода";
$MESS ["COMPONENT_TEMPLATE_JS_CALLBACK_HELP"] = "Укажите имя вашей js-функии, которой будет передан результат проверки кода Giftd, после того как скрипт компонета <b>giftd:coupon_input</b> проверит введенный код";

$MESS ["JS_PANEL_IS_ACTIVE"] = "Отображать на сайте графический блок Giftd";
$MESS ["JS_TAB_POSITION"] = "Местоположение вкладки Giftd";
$MESS ["JS_TAB_POSITION_LEFT"] = "Слева";
$MESS ["JS_TAB_POSITION_RIGHT"] = "Справа";
$MESS ["JS_TAB_POSITION_BOTTOM"] = "Снизу";
$MESS ["JS_PANEL_DECOR_IS_ACTIVE"] = "Настроить внешний вид вкладки Giftd";
$MESS ["JS_TAB_PANEL_BG_COLOR"] = "Свойство вкладки tab.panelBg.color";
$MESS ["JS_TAB_PANEL_BG_IMAGE"] = "Свойство вкладки tab.panelBg.image";
$MESS ["JS_TAB_PANEL_DECOR_TOP"] = "Свойство вкладки tab.panelDecor.top";
$MESS ["JS_TAB_PANEL_DECOR_BOTTOM"] = "Свойство вкладки tab.panelDecor.bottom";
$MESS ["JS_PANEL_TEXT_COLOR"] = "Свойство вкладки tab.panelTextColor";
$MESS ["JS_PANEL_DESCRIPTION_COLOR"] = "Свойство вкладки tab.panelDescriptionColor";
$MESS ["JS_PANEL_DESCRIPTION_ICON"] = "Свойство вкладки tab.panelDescriptionIcon";
$MESS ["JS_CONTENT_BG_IMAGE"] = "Свойство вкладки tab.contentBgImage";
$MESS ["JS_CONTENT_COLOR"] = "Свойство вкладки tab.contentColor";
$MESS ["JS_CONTENT_TITLE_COLOR"] = "Свойство вкладки tab.contentTitleColor";

?>