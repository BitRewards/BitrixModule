<?
IncludeModuleLangFile(__FILE__);
$base_code = file_get_contents($_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/catalog/general/base_discount_coupon.php');

eval('namespace giftdpatched; use \CCatalogDiscountCoupon as CCatalogDiscountCoupon; use \CCatalog as CCatalog; ?>'.$base_code);

class CAllCatalogDiscountCoupon extends giftdpatched\CAllCatalogDiscountCoupon
{
    public function SetCoupon($coupon)
    {
        $result = parent::SetCoupon($coupon);

        if(!$result && GiftdDiscountManager::SetCoupon($coupon))
        {
            $result = parent::SetCoupon($coupon);
        }

        return $result;
    }

    public function GetCoupons()
    {
        $coupons = parent::GetCoupons();

        if(CModule::IncludeModule('sale'))
        {
            $basket_sum = self::GetBasketSum();
            foreach($coupons as $coupon)
            {
                $card = GiftdDiscountManager::GetGiftdCouponCard($coupon);
                if($card &&
                    $card->token_status == Giftd_Card::TOKEN_STATUS_OK &&
                    $card->min_amount_total > $basket_sum)
                {
                    parent::EraseCoupon($coupon);
                }
            }
        }

        return parent::GetCoupons();
    }

    private function GetBasketSum()
    {
        $q = CSaleBasket::GetList(Array(), Array("FUSER_ID"=>CSaleBasket::GetBasketUserID(), "ORDER_ID"=>false));
        $sum=0;
        while ($item = $q->GetNext()) {
            $sum += $item["PRICE"]*$item["QUANTITY"];
        }

        return $sum;
    }
}

?>