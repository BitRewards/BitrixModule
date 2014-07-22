<?
$module_id = 'giftd.coupon';

CModule::AddAutoloadClasses(
    $module_id,
    array(
        "GCouponAdmin" => "general/admin.php",
        "GiftdHelper" => "general/giftd_helper.php",
        "GiftdClient" => "general/giftd_api_client.php",
        "GiftdDiscountManager" => "general/giftd_discount_manager.php",
    )
);

?>
