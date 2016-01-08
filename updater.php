<?php

global $DB;
$result = $DB->Update("b_sale_discount", array('USE_COUPONS' => "'Y'"), "WHERE NAME LIKE 'Giftd%'");

RegisterModuleDependences('sale', 'onAfterDeleteDiscountCoupons', 'giftd.coupon', 'GiftdDiscountManager', 'FixUseCouponsFlagAfterDelete');
RegisterModuleDependences('sale', 'DiscountCouponOnAfterDelete', 'giftd.coupon', 'GiftdDiscountManager', 'FixUseCouponsFlagAfterDelete');
RegisterModuleDependences('sale', 'DiscountCouponOnBeforeDelete', 'giftd.coupon', 'GiftdDiscountManager', 'RestrictGiftdCouponDelete');