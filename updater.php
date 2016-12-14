<?php

RegisterModuleDependences('sale', 'OnSaleOrderBeforeSaved', 'giftd.coupon', 'GiftdDiscountManager', 'ChargeCouponOnBeforeOrderAddD7Events');
RegisterModuleDependences('sale', 'OnSaleOrderEntitySaved', 'giftd.coupon', 'GiftdDiscountManager', 'UpdateExternalIdAfterOrderSaveD7Events');