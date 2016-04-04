<?php


if (version_compare(SM_VERSION, "15.0.0") >= 0) {
    $root = $_SERVER["DOCUMENT_ROOT"] . BX_ROOT;
    if (file_exists($root . '/modules/catalog/general/base_discount_coupon.php')) {
        CopyDirFiles($root . '/modules/catalog/general/base_discount_coupon.php', $root . '/modules/catalog/general/discount_coupon.php', true);
        unlink($root . '/modules/catalog/general/base_discount_coupon.php');
    }    
}

