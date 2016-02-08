<?php

RegisterModuleDependences('sale', 'OnBeforeOrderAdd', 'giftd.coupon', 'GiftdDiscountManager', 'ChargeCouponOnBeforeOrderAdd');
RegisterModuleDependences('sale', 'OnOrderAdd', 'giftd.coupon', 'GiftdDiscountManager', 'UpdateExternalIdAfterOrderSave');