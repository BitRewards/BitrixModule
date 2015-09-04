<?php
/**
 * Created by PhpStorm.
 * User: Kirill
 * Date: 7/31/14
 * Time: 12:57 AM
 */
require_once('giftd_settings.php');

class GiftdComponentSettings extends GiftdSettings implements IGiftdSettings
{
    protected $_default_class = 'class="optional"';
    protected $options =
        array(
            // 'COMPONENT_IS_ACTIVE' => array('HeadingTableRow'=>array('Checkbox')),
            // 'COMPONENT_IS_ACTIVE_HELP' => array('OneColumnRow'=>array('Help')),
            'COMPONENT_TEMPLATE' => array('SettingTableRow'=>array('Select'=>array('COMPONENT_TEMPLATE_TYPE_PHP'=>'PHP', 'COMPONENT_TEMPLATE_TYPE_PHPJS'=>'PHPJS', 'COMPONENT_TEMPLATE_TYPE_HTML'=>'HTML'), 'class="optional component_field"')),
            'COMPONENT_TEMPLATE_HELP' => array('ValueTableRow'=>array('Help', 'class="optional component_field"')),
            'COMPONENT_TEMPLATE_JS_COUPON_FIELD_ID' => array('SettingTableRow'=>array('Text', 'class="optional component_field  template_field"')),
            'COMPONENT_TEMPLATE_JS_COUPON_FIELD_ID_HELP' => array('ValueTableRow'=>array('Help', 'class="optional component_field template_field"')),
            'COMPONENT_TEMPLATE_JS_CALLBACK' => array('SettingTableRow'=>array('Text', 'class="optional component_field template_field"')),
            'COMPONENT_TEMPLATE_JS_CALLBACK_HELP' => array('ValueTableRow'=>array('Help', 'class="optional component_field template_field"')),
        );


    protected function Help($key)
    {
        return GetMessage($key);
    }
}
?>