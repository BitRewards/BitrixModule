<?
IncludeModuleLangFile(__FILE__);
$base_code = file_get_contents($_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/catalog/general/base_discount_coupon.php');

eval('namespace giftdpatched; use \CCatalogDiscountCoupon as CCatalogDiscountCoupon; use \CCatalog as CCatalog; ?>'.$base_code);

class CAllCatalogDiscountCoupon extends giftdpatched\CAllCatalogDiscountCoupon
{
    public function SetCoupon($coupon)
    {
        $result = parent::SetCoupon($coupon);

        if (!$result && GiftdDiscountManager::SetCoupon($coupon)) {
            $result = parent::SetCoupon($coupon);
        }

        return $result;
    }

}