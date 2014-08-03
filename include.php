<?php
$module_id = 'giftd.coupon';

CModule::AddAutoloadClasses(
    $module_id,
    array(
        "IHtmlBuilder" => "general/IHtmlBuilder.php",
        "GCouponAdmin" => "general/admin.php",
        "GiftdHelper" => "general/giftd_helper.php",
        "GiftdClient" => "general/giftd_api_client.php",
        "GiftdDiscountManager" => "general/giftd_discount_manager.php",
        "GiftdPanelSettings" => "general/giftd_panel_settings.php",
        "GiftdComponentSettings" => "general/giftd_component_settings.php",
        "GenericHtmlBuilder" => "general/html_builder.php",
    )
);
