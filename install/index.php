<?php

global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));

class giftd_coupon extends CModule
{
	var $MODULE_ID = "giftd.coupon";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME = "GIFTD";
	var $MODULE_DESCRIPTION = "GIFTD.TECH Integration Module";
	var $MODULE_GROUP_RIGHTS = "Y";

	function giftd_coupon()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		if ($message = GetMessage("GIFTD_MODULE_NAME")) {
            $this->MODULE_NAME = $message;
        }

        if ($message = GetMessage("GIFTD_MODULE_DESCRIPTION")) {
            $this->MODULE_DESCRIPTION = $message;
        }

        $this->PARTNER_NAME = "Giftd";
        $this->PARTNER_URI = "https://partner.giftd.ru";
    }


	function Register()
	{
        RegisterModule($this->MODULE_ID);
        RegisterModuleDependences('main', 'OnBeforeProlog', $this->MODULE_ID, 'GiftdHelper', 'CheckPatchOnBeforeProlog');
        RegisterModuleDependences('main', 'OnBeforeProlog', $this->MODULE_ID, 'GiftdHelper', 'InjectJSTabScriptOnBeforeProlog');
        RegisterModuleDependences('main', 'OnAfterEpilog', $this->MODULE_ID, 'GiftdHelper', 'ReplaceTopWithParentOnAfterEpilog');
        RegisterModuleDependences('catalog', 'OnGetOptimalPrice', $this->MODULE_ID, 'GiftdDiscountManager', 'AdjustPriceOnGetOptimalPrice');
        RegisterModuleDependences('catalog', 'OnGetOptimalPriceResult', $this->MODULE_ID, 'GiftdDiscountManager', 'AdjustPriceOnGetOptimalPriceResult');
        RegisterModuleDependences('catalog', 'OnBeforeCouponDelete', 'giftd.coupon', 'GiftdDiscountManager', 'RestrictGiftdCouponDelete');

        RegisterModuleDependences('sale', 'onBuildCouponProviders', $this->MODULE_ID, 'GiftdDiscountManager', 'OnBuildCouponProviders');
        RegisterModuleDependences('sale', 'onAfterDeleteDiscountCoupons', 'giftd.coupon', 'GiftdDiscountManager', 'FixUseCouponsFlagAfterDelete');
        RegisterModuleDependences('sale', 'DiscountCouponOnAfterDelete', 'giftd.coupon', 'GiftdDiscountManager', 'FixUseCouponsFlagAfterDelete');
        RegisterModuleDependences('sale', 'DiscountCouponOnBeforeDelete', 'giftd.coupon', 'GiftdDiscountManager', 'RestrictGiftdCouponDelete');

        RegisterModuleDependences('sale', 'OnOrderSave', $this->MODULE_ID, 'GiftdDiscountManager', 'ChargeCouponOnOrderSave');
		RegisterModuleDependences('sale', 'OnBeforeOrderAdd', 'giftd.coupon', 'GiftdDiscountManager', 'ChargeCouponOnBeforeOrderAdd');
		RegisterModuleDependences('sale', 'OnOrderAdd', 'giftd.coupon', 'GiftdDiscountManager', 'UpdateExternalIdAfterOrderSave');

        RegisterModuleDependences('sale', 'OnSaleOrderBeforeSaved', $this->MODULE_ID, 'GiftdDiscountManager', 'ChargeCouponOnBeforeOrderAddD7Events');
        RegisterModuleDependences('sale', 'OnSaleOrderEntitySaved', $this->MODULE_ID, 'GiftdDiscountManager', 'UpdateExternalIdAfterOrderSaveD7Events');

        return true;
	}

	function UnRegister()
	{
        UnRegisterModuleDependences('main', 'OnBeforeProlog', $this->MODULE_ID, 'GiftdHelper', 'CheckPatchOnBeforeProlog');
        UnRegisterModuleDependences('main', 'OnBeforeProlog', $this->MODULE_ID, 'GiftdHelper', 'InjectJSTabScriptOnBeforeProlog');
        UnRegisterModuleDependences('main', 'OnAfterEpilog', $this->MODULE_ID, 'GiftdHelper', 'ReplaceTopWithParentOnAfterEpilog');
		UnRegisterModuleDependences('sale', 'OnBeforeOrderAdd', $this->MODULE_ID, 'GiftdDiscountManager', 'ChargeCouponOnBeforeOrderAdd');
        UnRegisterModuleDependences('sale', 'OnOrderSave', $this->MODULE_ID, 'GiftdDiscountManager', 'ChargeCouponOnOrderSave');
        UnRegisterModuleDependences('catalog', 'OnGetOptimalPrice', $this->MODULE_ID, 'GiftdDiscountManager', 'AdjustPriceOnGetOptimalPrice');
        UnRegisterModuleDependences('catalog', 'OnGetOptimalPriceResult', $this->MODULE_ID, 'GiftdDiscountManager', 'AdjustPriceOnGetOptimalPriceResult');

        UnRegisterModuleDependences('sale', 'onBuildCouponProviders', $this->MODULE_ID, 'GiftdDiscountManager', 'OnBuildCouponProviders');

		UnRegisterModuleDependences('sale', 'onAfterDeleteDiscountCoupons', 'giftd.coupon', 'GiftdDiscountManager', 'FixUseCouponsFlagAfterDelete');
		UnRegisterModuleDependences('sale', 'DiscountCouponOnAfterDelete', 'giftd.coupon', 'GiftdDiscountManager', 'FixUseCouponsFlagAfterDelete');
		UnRegisterModuleDependences('sale', 'DiscountCouponOnBeforeDelete', 'giftd.coupon', 'GiftdDiscountManager', 'RestrictGiftdCouponDelete');
		UnRegisterModuleDependences('catalog', 'OnBeforeCouponDelete', 'giftd.coupon', 'GiftdDiscountManager', 'RestrictGiftdCouponDelete');
		UnRegisterModuleDependences('sale', 'OnBeforeOrderAdd', 'giftd.coupon', 'GiftdDiscountManager', 'ChargeCouponOnBeforeOrderAdd');
		UnRegisterModuleDependences('sale', 'OnOrderAdd', 'giftd.coupon', 'GiftdDiscountManager', 'UpdateExternalIdAfterOrderSave');


        UnRegisterModuleDependences('sale', 'OnSaleOrderBeforeSaved', 'giftd.coupon', 'GiftdDiscountManager', 'ChargeCouponOnBeforeOrderAddD7Events');
        UnRegisterModuleDependences('sale', 'OnSaleOrderEntitySaved', 'giftd.coupon', 'GiftdDiscountManager', 'UpdateExternalIdAfterOrderSaveD7Events');

		UnRegisterModule($this->MODULE_ID);

		return true;
	}


	function InstallFiles()
	{
		// CopyDirFiles($_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/'.$this->MODULE_ID.'/install/components', $_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/components', true, true);

        return true;
	}


	function UnInstallFiles()
	{
        $root = $_SERVER["DOCUMENT_ROOT"] . BX_ROOT;
        if (file_exists($root . '/modules/catalog/general/base_discount_coupon.php')) {
            CopyDirFiles($root . '/modules/catalog/general/base_discount_coupon.php', $root . '/modules/catalog/general/discount_coupon.php', true);
            unlink($root . '/modules/catalog/general/base_discount_coupon.php');
        }

        //DeleteDirFiles($_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/'.$this->MODULE_ID.'/install/components', $_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/components');

        return true;
	}

	function DoInstall()
	{
		$this->InstallFiles();
		$this->Register();

		return true;
	}

	function DoUninstall()
	{
        GiftdHelper::handleUninstall(
            GiftdHelper::GetOption('API_KEY'),
            GiftdHelper::GetOption('USER_ID')
        );

        $this->UnRegister();
        $this->UnInstallFiles();

        return true;
	}
}
?>