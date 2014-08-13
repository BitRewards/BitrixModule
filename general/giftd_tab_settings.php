<?php
/**
 * Created by PhpStorm.
 * User: Kirill
 * Date: 7/31/14
 * Time: 12:57 AM
 */
require_once('giftd_settings.php');

class GiftdTabSettings extends GiftdSettings implements IGiftdSettings
{
    protected $_default_class = 'class="optional tab_field"';
    protected $options = null;

    public function __construct($module_id, IHtmlBuilder $html_builder)
    {
        $this->options =
            array(
                'JS_TAB_IS_ACTIVE' => array('HeadingTableRow'=>array('Checkbox')),
                array('ValueTableRow'=>array('TabDisabledHtml', 'class="optional tab_disabled_field"')),
                'JS_TAB_POSITION' => array('SettingTableRow'=>array('Radio'=>array(GetMessage('JS_TAB_POSITION_LEFT') => 'left', GetMessage('JS_TAB_POSITION_RIGHT') => 'right', GetMessage('JS_TAB_POSITION_BOTTOM') => 'bottom'))),
                array('ValueTableRow'=>array('BannerHtml')),
                'JS_TAB_CUSTOMIZE' => array('HeadingTableRow'=>array('Checkbox', 'class="heading tab_field"')),
                array('ValueTableRow'=>array('CustomizeHtml', 'class="optional tab_customize_field"')),
                'JS_TAB_OPTIONS' => array('SettingTableRow'=>array('Textarea' => GiftdHelper::getDefaultJsOptions(), 'class="optional tab_customize_field giftd-code"')),
            );

        parent::__construct($module_id, $html_builder);
    }

    protected function BannerHtml($key)
    {
        return '<img src="https://partner-static.giftd.ru/img/embedded_tab_screenshot.png">';
    }

    protected function TemplateHtml($key)
    {
        return '<img style="width: 600px;" src="https://partner-static.giftd.ru/img/embedded_customization.png">';
    }

    protected function TabDisabledHtml($key)
    {
        ob_start();
        ?>
        <p><b>Вы отключили показ стандартного блока Giftd</b> — однако для корректной работы сервиса нужно, чтобы встраиваемое решение Giftd открывалось после клика на каком-нибудь элементе (например, на пункте меню или кнопке в подвале).</p>
        <p>
            Для того, чтобы встраиваемое решение Giftd открывалось на вашем сайте после клика на каком-либо
            элементе, необходимо добавить к нему класс <code>js-giftd-open</code>. Например, так:
        </p>
        <pre>&lt;a href="#" class="js-giftd-open"&gt;Наши подарочные карты&lt;/a&gt;</pre>
        <p>
            Если вам по каким-либо причинам не подходит этот метод, можете напрямую вызывать метод <code>Giftd.show()</code>:
        </p>
        <pre>...
if (window.Giftd) {
    Giftd.show();
}
...</pre>
        <?php
        return ob_get_clean();
    }

    protected function CustomizeHtml($key)
    {
        ob_start();
        ?>
        <p>
            Ниже вы можете отредактировать JS-код, который встраивается на ваш сайт.
            В этом коде есть ряд переменных, которые вы можете изменить — <b>цвета надписей, фоновые цвета и изображения</b>.
        </p>
        <p>
            Это образец кода, в котором всем возможным переменным заданы значения — эти значения приводят цвета и изобажения вкладки Giftd в состояние "по умолчанию":
        </p>
        <style>
            pre.giftd-code {
                background-color: ghostwhite;
                border: 1px solid silver;
                padding: 10px 20px;
                margin: 20px;
                max-width: 600px;
                overflow: scroll;;

            }
            .giftd-code .json-key {
                color: brown;
            }
            .giftd-code .json-value {
                color: navy;
            }
            .giftd-code .json-string {
                color: olive;
            }
            .giftd-code textarea {
                width: 600px;
                height: 365px;
                font-family: courier, monospace;
                font-size: 12px;
                line-height: 15px;
                white-space: nowrap;
                overflow: auto;
            }
        </style>

        <pre class="giftd-code"><code><span class="json-key">window.giftdOptions</span> = {
    <span class="json-key">pid</span>: <span class="json-string">"<?php echo GiftdHelper::GetOption('PARTNER_CODE') ?>"</span>,
    <span class="json-key">tab</span>: {
        <span class="json-key">enabled</span>: <span class="json-value">true</span>,
        <span class="json-key">position</span>: <span class="json-string">"left"</span>,
        <span class="json-key">panelBg</span>: {
            <span class="json-key">color</span>: <span class="json-string">"#008ede"</span>,
            <span class="json-key">image</span>: <span class="json-string">"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAwCAYAAADQMxCBAAAAVklEQVQIHW2OUQ6AMAhDCSfz2DvbhOJgDDSRr5aWF+gaMKY1rAClUiOGLXu7ylTfdopkMPWcocr7wgGhAOdRob69qEhRwu5dPdS7fjIrB9qVUPrD8+ABBOQ3zPy6HCkAAAAASUVORK5CYII="</span>
        },
        <span class="json-key">panelDecor</span>: {
            <span class="json-key">top</span>: <span class="json-string">"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADsAAAAxCAMAAABTYCHnAAAAwFBMVEUAAAAAAQEAAQEAAQEAAQEAAQEAAQEAAQEAAQEAAQEAAQEAAQHO3usAAQEAAQEAAQHkge3A1eaouca80N/D1+enusnZ5e+0w87V4+79/f3kge3O3uv5+vze6fLTfuj6+/y7ytbIcdC2z+LTd9vL3OrpmfHvt/T79Pz8+PzG2OjhgOzFddm9a8X67PvKfeTxvvW+0uP00Pfnje/lhe7spfLB1ubN3eu/1OWzxdSrwNHy9vn33fnzyvbZe+LtrfPWed+d0tzIAAAAGXRSTlMANSM5KRIDGgwHPDFmHxYvRGaqF0m22bXy9/6YcAAAAZxJREFUSMfl1l1XgjAcBnBQQEzASnsZYqkgmIn4Vim+9P2/Vftv1RyE4+yCm54bOJzzO3C2sT2KohjNpqk7Ddu2a3fbEU4vRT/x+k8uDsomve8oQA1Ka5p2uwMaICSwkMdfS2ib0FFayqZdbDE1HUzbLZVaj7dH3/fjQ5gsIx7fUKs7tqa1VHW3gPD2dTabHV3IOpzkLXyy1lbV+u4DkrXDqfudcfSXreHX1q+2b5CsJZRmc8G+QDL20z3LUmCfeUu/NhzTa7ENILydA0nw7YlgNlwWbwfM5uc3JjYutO+QAjsRvLcHKbAHuFkhGRut4CaUsiFZWpGMjdd0emXsBi4nJGP3ZKAiKUtW1R7J2ISOsZSFjCcXrU/inmU6x7Yg15wdkvwfKz9W4jmqeG2wNVn5v4A2bGuX/vcr3nPYXlflHiu/t4vPFGYlzzJmxWeo2ObPbrEVdwaL7yqcFXUVi+9InBV1JIvrZszmu1mS62YW3wkHAdhyndDrZrroAmy/lO1jynXgh0UA2BNYeN6hlmDdcRol4ui6aTYNA+AX7gz9bC873/cAAAAASUVORK5CYII="</span>,
            <span class="json-key">bottom</span>: <span class="json-string">"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAKgAAAAwCAMAAACyoMGoAAAAe1BMVEUAAAA5s/sfou4sq/QQmOYZnusIk+MIk+MTmug4svo+tv0HkuIlpvAWnOk1sPgLlOQZnusJk+McoOwwrfUyrvcdoOwTmug3sfkBjt8doe0ZnusUm+gEkOERmOcLleQ1sPg6tPtAuP4Hk+IOl+Uyrvctq/QlpvA+tvwbn+w2IVSMAAAAGHRSTlMAH8bFWWRKxjYlVufFj1bnjy6PzO7yx1h3FiyaAAABl0lEQVRYw+2R21KDQBBENwYjq0bFaJVKvCfR//9CQ+h5sKlJF1CWS8KBl+6ZhVMQPPJYw/07iJMa7lU+B2qPCR5RiMIzJiv6BUyUe5VNVO0xPUVjuqJvwES5V9lE1R7TWvQVmCj3Kl8BtcccrujdqoZFV0BlE1V7DMaaxbRisfBeMAVB0FaMsoZF1qCtKJ9bE948AdHN7gJIPUQvgeUN8OYMn+PM8PzwRE+A5UfgzRk+x5nheQqim+adpmi3L3oKgsM9HcyB9+Bb4AmovrPoN2BRnrMo95a9nhm2aLZH9AmwqDe/AdxbFj3N+YsORjTbK1puL1eU53Ow7X71yGWjR8FX619fAhblOYtyb9nrGe/Xz5MTzUDoSQlUVudHUeMZqOyeF4yiyYp+ApXd84LjE30BKrvnBcHjDFieFBVxogSzWQ0/h/evQSCEoBaNRZHnW1MlOvtv0cqzMk1fNN/REP0ASpT3L0DjeURX0XwoooP5ogMQLSBKLIES5X1PdEmEv4HF9YtRS0bR5EUfAGfD2ZeMoqNo6qI/fvJUFNWbkq8AAAAASUVORK5CYII="</span>
        },
        <span class="json-key">panelTextColor</span>: <span class="json-string">"#fff"</span>,
        <span class="json-key">panelDescriptionColor</span>: <span class="json-string">"#B0FFA5"</span>,
        <span class="json-key">panelDescriptionIcon</span>: <span class="json-string">"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAATCAMAAACuuX39AAAAwFBMVEUAAACw/6Ww/6UAAQGn8pwAAQGT1ooFCAUAAQEAAQGv/aQAAQGw/6UAAQE0TTGq9qCw/6Ww/6UOFQ6Q0Ieu/KOw/6VfiVlwo2k2TzMAAQGV2Iyw/6Ww/6WR0oiw/6Wq96Ct+6Kd45Oi65ig6Jap9Z+Gwn6w/6UAAQGw/6Ww/6Wi6pew/6WIxYCFwHxKbEav/aSw/6Wm8Juw/6Wq9p9jj12w/6VMb0iq96Cw/6Ww/6Wm8Zyj7JmW2o2w/6Wu/KOw/6XJxdjGAAAAP3RSTlMAEFEKBi2vHyUQQzzWBUzo+wo5k6dcamsaAlZ9squq+n2apNLelzsbMZ23FomVX41zvmXXROJdw/Ul0ca3//4rIEY4AAAAmUlEQVQY003O5Q7DMAwE4BSTFLXimJmZ3fd/rNXT1vj+3SfrZEZS01ONdu0CcH8Q6EOZhMAQ4an6fIwQ/+tqAfGkV3R4ud1KUnMPy4Bz1xWMmR5enjLL/906gIlktdX8gqnG6yHCTr3YCOzRZnuAYyW+sLhh5aHnTLttvWIZrQGjhM1shBtTGSC8CJyLEnICxtV+Z5JREUKyD26+FQN53O4ZAAAAAElFTkSuQmCC"</span>,
        <span class="json-key">contentBgImage</span>: <span class="json-string">"https://static.giftd.ru/embedded/content_bg.png?1100409742"</span>,
        <span class="json-key">contentColor</span>: <span class="json-string">"#5d869e"</span>,
        <span class="json-key">contentTitleColor</span>: <span class="json-string">"#3496CE"</span>
    }
}</code></pre>
        <p>
            На картинке показано соответствие между переменными и элементами встраиваемого решения:
        </p>
        <img style="width: 600px;" src="https://partner-static.giftd.ru/img/embedded_customization.png">
        <p>Нюансы кастомизации встраиваемого решения:</p>
        <ul>
            <li>В образце кода все переменные заполнены значениями, равными значениям по умолчанию;</li>
            <li>Свойство <code>position</code> может принимать одно из трех значений: <code>left</code>, <code>right</code> или <code>bottom</code>.</li>
            <li>Не меняйте свойство <code>pid</code>;</li>
            <li>Мы советуем удалить из кода переменные, которые вы не будете кастомизировать — будут использованы значения по умолчанию;</li>
            <li>Не забудьте раскомментировать строчку с переменной, если меняете ее;</li>
            <li>Картинки можно вставлять как в виде Data URI, так и в виде обычного URL — в образце выше есть и тот, и другой вариант.</li>
            <li>Для задания фона панели вы можете использовать как свойство <code>panelBg.color</code>, так и <code>panelBg.image</code>. <br><small><i>Рекомендуем использовать обе переменных одновременно — на тот случай, если фоновая картинка не загрузится.</i></small></li>

        </ul>
<?php
        return ob_get_clean();
    }

}