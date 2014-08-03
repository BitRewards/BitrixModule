<?php
/**
 * Created by PhpStorm.
 * User: Kirill
 * Date: 7/31/14
 * Time: 12:57 AM
 */
require_once('giftd_settings.php');

class GiftdPanelSettings extends GiftdSettings implements IGiftdSettings
{
    protected $_default_class = 'class="optional panel_field"';
    protected $options = null;

    public function __construct($module_id, IHtmlBuilder $html_builder)
    {
        $this->options =
            array(
                'JS_PANEL_IS_ACTIVE' => array('HeadingTableRow'=>array('Checkbox')),
                'JS_TAB_POSITION' => array('SettingTableRow'=>array('Radio'=>array(GetMessage('JS_TAB_POSITION_LEFT') => 'top', GetMessage('JS_TAB_POSITION_RIGHT') => 'right', GetMessage('JS_TAB_POSITION_BOTTOM') => 'bottom'))),
                array('ValueTableRow'=>array('BannerHtml')),
                'JS_PANEL_DECOR_IS_ACTIVE' => array('HeadingTableRow'=>array('Checkbox', 'class="heading panel_field"')),
                'JS_TAB_PANEL_BG_COLOR' => array('SettingTableRow'=>array('Text', 'class="optional panel_decor_field"')),
                'JS_TAB_PANEL_BG_IMAGE' => array('SettingTableRow'=>array('File', 'class="optional panel_decor_field"')),
                'JS_TAB_PANEL_DECOR_TOP' => array('SettingTableRow'=>array('File', 'class="optional panel_decor_field"')),
                'JS_TAB_PANEL_DECOR_BOTTOM' => array('SettingTableRow'=>array('File', 'class="optional panel_decor_field"')),
                'JS_PANEL_TEXT_COLOR' => array('SettingTableRow'=>array('Text', 'class="optional panel_decor_field"')),
                'JS_PANEL_DESCRIPTION_COLOR' => array('SettingTableRow'=>array('Text', 'class="optional panel_decor_field"')),
                'JS_PANEL_DESCRIPTION_ICON' => array('SettingTableRow'=>array('File', 'class="optional panel_decor_field"')),
                'JS_CONTENT_BG_IMAGE' => array('SettingTableRow'=>array('File', 'class="optional panel_decor_field"')),
                'JS_CONTENT_COLOR' => array('SettingTableRow'=>array('Text', 'class="optional panel_decor_field"')),
                'JS_CONTENT_TITLE_COLOR' => array('SettingTableRow'=>array('Text', 'class="optional panel_decor_field"')),
                array('ValueTableRow'=>array('TemplateHtml', 'class="optional panel_decor_field"'))
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
}

?>