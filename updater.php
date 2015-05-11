<?php

function __updateGiftdDiscounts() {
    if (isset($_SERVER["DOCUMENT_ROOT"])) {
        require_once $_SERVER["DOCUMENT_ROOT"] . BX_ROOT . '/modules/catalog/include.php';
    }

    $q = CCatalogDiscount::GetList(array(), array('~NAME'=>'Giftd%'), false, false, array("ID", "NAME"));
    while ($discount = $q->GetNext()) {
        $updateData = array(
            "VALUE_TYPE" => "F",
            "VALUE" => 0.01
        );
        $r = CCatalogDiscount::Update($discount['ID'], $updateData);
    }
}

RegisterModuleDependences('catalog', 'OnGetOptimalPriceResult', 'giftd.coupon', 'GiftdDiscountManager', 'AdjustPriceOnGetOptimalPriceResult');
RegisterModuleDependences('sale', 'onBuildCouponProviders', 'giftd.coupon', 'GiftdDiscountManager', 'OnBuildCouponProviders');

UnRegisterModuleDependences('catalog', 'OnGetOptimalPriceResult', 'giftd.coupon', 'GiftdDiscountManager', 'AdjustPriceOnGetOptimalPrice');

__updateGiftdDiscounts();

$originalFilePath = $_SERVER["DOCUMENT_ROOT"] . BX_ROOT . '/modules/catalog/general/base_discount_coupon.php';
$targetPath = $_SERVER["DOCUMENT_ROOT"] . BX_ROOT . '/modules/catalog/general/discount_coupon.php';
if (file_exists($originalFilePath)) {
    CopyDirFiles($originalFilePath, $targetPath, true);
    unlink($originalFilePath);
}