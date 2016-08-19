<?php

RegisterModuleDependences('sale', 'OnSaleOrderBeforeSaved', $this->MODULE_ID, 'GiftdDiscountManager', 'ChargeCouponOnBeforeOrderAddD7Events');
RegisterModuleDependences('sale', 'OnSaleOrderEntitySaved', $this->MODULE_ID, 'GiftdDiscountManager', 'UpdateExternalIdAfterOrderSaveD7Events');