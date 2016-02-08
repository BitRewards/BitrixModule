<?
$MESS ['SAVE'] = "Сохранить";
$MESS ['RESET'] = "Сбросить";

$MESS ['MODULE_API_SETTINGS'] = "Доступ к API";
$MESS ['SIGN_IN'] = "Получить параметры для доступа к API Giftd";
$MESS ['API_KEY'] = "Ключ API Giftd (api_key)";
$MESS ['SETTINGS'] = "Дополнительные настройки";
$MESS ['USER_ID'] = "ID пользователя Giftd (user_id)";
$MESS ['PARTNER_CODE'] = "Код партнера Giftd";
$MESS ['PARTNER_TOKEN_PREFIX'] = "Префикс кодов подарочных карт";
$MESS ['DISABLE_BASKET_RULES'] = "Отключить использование правил корзины (и использовать скидки на товар)";


$MESS ['COMPONENT_IS_ACTIVE'] = "Проверять вводимые промо-коды через Giftd API";
$MESS ['COMPONENT_IS_ACTIVE_HELP'] = "Отметьте галочку выше, чтобы включить наше готовое решение для валидации через наше API промо-кодов, которые вводят пользователи.<br>Наше решение — это доработанная стандартная логика скидочных купонов Битрикса.<br><br>Если у вас сильно кастомизированы стандартные модули catalog или sale (в частности, их логика работы с купонами) — <br>не отмечайте галочку выше и напишите на <a href='partner@giftd.ru'>partner@giftd.ru</a>; мы обязательно что-нибудь придумаем.<br><Br><br>";
$MESS ["COMPONENT_TEMPLATE"] = "Шаблон компонента";
$MESS ["COMPONENT_TEMPLATE_JS_COUPON_FIELD_ID"] = "ID элемента, в который вводятся коды подарочных карт";
$MESS ["COMPONENT_TEMPLATE_JS_CALLBACK"] = "JS функция, вызываемая после обработки кода подарочной карты";

$MESS ["COMPONENT_TEMPLATE_HELP"]  = '<p><b>Совет</b>: если на экране просмотра корзины уже есть поле для ввода скидочного кода, то скорее всего вам подойдет самый простой вариант — «<b>Серверный обработчик</b>»</p>';
$MESS ["COMPONENT_TEMPLATE_HELP"] .= '<p>1. Используя шаблон «<b>Серверный обработчик</b>», вы целиком доверяете проверку кодов штатным механизмам Битрикса. <ul><li>Если же вам нужно самостоятельно проверять код подарочной карты — выполняйте POST-запрос в формате JSON <code>{coupon: "secret_gift_token"}</code> серверному обработчику по адресу <code style="white-space: nowrap;">/bitrix/components/giftd/coupon_input/ajax.php</code></li></ul></p>';
$MESS ["COMPONENT_TEMPLATE_HELP"] .= '<p>2. При использовании компонента «<b>giftd:coupon_input</b>» с шаблоном «<b>Серверный обработчик и AJAX-валидатор</b>", на странице, где был размещен компонент, будет автоматически встроен JS-код проверки кодов Giftd. Код будет использовать вводимые ниже ID элемента и JS-функцию.</p>';
$MESS ["COMPONENT_TEMPLATE_HELP"] .= '<p>3. При использовании компонента «<b>giftd:coupon_input</b>» с шаблоном «<b>Серверный обработчик, AJAX-валидатор и HTML-форма</b>", на странице, где был размещен компонент, будет автоматически встроены скрипт проверки кодов Giftd и поле для ввода кодов вида <code style="white-space: nowrap;">&lt;input type="text" name="coupon"&gt;</code></p>';
$MESS ["COMPONENT_TEMPLATE_JS_COUPON_FIELD_ID_HELP"] = "Укажите параметр id вашего поля для ввода скидочных кодов, при изменении которого скрипт компонента <b>giftd:coupon_input</b> отправит запрос на проверку кода";
$MESS ["COMPONENT_TEMPLATE_JS_CALLBACK_HELP"] = "Укажите имя вашей js-функии, которой будет передан результат проверки кода Giftd, после того как скрипт компонета <b>giftd:coupon_input</b> проверит введенный код";

$MESS ["JS_TAB_IS_ACTIVE"] = "Отображать на сайте графический блок Giftd";
$MESS ["JS_TAB_POSITION"] = "Местоположение вкладки Giftd";
$MESS ["JS_TAB_POSITION_LEFT"] = "Слева";
$MESS ["JS_TAB_POSITION_RIGHT"] = "Справа";
$MESS ["JS_TAB_POSITION_BOTTOM"] = "Снизу";
$MESS ["JS_TAB_CUSTOMIZE"] = "Настроить внешний вид графического блока Giftd";
$MESS ["JS_TAB_OPTIONS"] = "JS-код с настройками графического блока Giftd";

$MESS ["JS_TAB_OPTIONS_SYNTAX_ERROR"] = "Введенный код с настройками блока Giftd не является валидным JS";
$MESS ["JS_TAB_OPTIONS_ERROR"] = "Введенный код с настройками блока Giftd невалиден: в нем либо не устанавливается window.giftdOptions, либо неверно устанавливается свойство window.giftdOptions.pid";