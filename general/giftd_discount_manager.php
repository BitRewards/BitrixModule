<?php

class GiftdDiscountManager
{
    private static $_client = null;
    private static $_module_id = 'giftd.coupon';
    private static $_code_prefix = '';
    public static $last_error = '';


    private static function Init()
    {
        if(self::$_client != null)
            return true;

        $api_key = COption::GetOptionString(self::$_module_id, 'API_KEY');
        $user_id = COption::GetOptionString(self::$_module_id, 'USER_ID');
        self::$_code_prefix = COption::GetOptionString(self::$_module_id, 'PARTNER_TOKEN_PREFIX');

        if(strlen($api_key) == 0 || strlen($user_id) == 0)
            return false;

        self::$_client = new GiftdClient($user_id, $api_key);
        return true;
    }

    private static function CreateDiscount($arFields)
    {
        $arEmpty = array('COUPON', 'MAX_DISCOUNT', 'ACTIVE_FROM', 'ACTIVE_TO', 'PRODUCT_IDS',  'SECTION_IDS', 'GROUP_IDS', 'CATALOG_GROUP_IDS', 'CATALOG_COUPONS', 'NOTES');
        foreach($arEmpty as $key)
        {
            if(!isset($arFields[$key]))
                $arFields[$key] = null;
        }

        return CCatalogDiscount::Add($arFields);
    }

    private static function CreateCoupon($arFields)
    {
        $CID = CCatalogDiscountCoupon::Add($arFields);
        $CID = IntVal($CID);
        return ($CID > 0) ? $CID : null;
    }

    private static function AddDiscountCoupon($coupon_code, $card_info)
    {
        $discount_name = 'Giftd-'.$card_info['amount'];
        $q = CCatalogDiscount::GetList(array(), $arFilter = array('NAME'=>$discount_name, 'ACTIVE' => 'Y'));
        $discount = $q->GetNext();

        $id_discount = $discount ? $discount['ID'] : 0;

        if($id_discount == 0)
        {
            $arDiscountFields    = array(
                'SITE_ID'           => 's1',
                'ACTIVE'            => 'Y',
                'NAME'              => $discount_name,
                'VALUE_TYPE'        => 'F',
                'VALUE'             => $card_info['amount'],
                'CURRENCY'          => 'RUB',
                'NOTES'             => 'Скидка реализующая подарочную карту '.$discount_name,
            );
            $id_discount = self::CreateDiscount($arDiscountFields);
        }


        if($id_discount > 0)
        {
            $arCouponFields = array(
                "DISCOUNT_ID" => $id_discount, //id for 150 RUB discount (first-time order)
                "ACTIVE" => "Y",
                "ONE_TIME" => "O",
                "COUPON" => $coupon_code,
                "DATE_APPLY" => false,
                'DESCRIPTION' => $card_info['title'].' ('.$card_info['owner'].')'
            );

            return self::CreateCoupon($arCouponFields);
        }

        return false;
    }

    public static function GetGiftdCouponCard($code)
    {
        if(strlen($code) == 0 || self::Init() == false || !strstr($code, self::$_code_prefix))
            return null;

        return self::$_client->checkByToken($code);
    }

    public static function SetCoupon($code, $any = false)
    {
        if(strlen($code) == 0 || self::Init() == false)
            return null;

        $arFilter = array("COUPON"=>$code, 'ACTIVE'=>'Y');

        if($any){ unset($arFilter['ACTIVE']); }

        $q = CCatalogDiscountCoupon::GetList(array(), $arFilter);
        $coupon = $q->GetNext();

        if(!$coupon)
        {
            $card = self::GetGiftdCouponCard($code);
            if($card && $card->token_status == Giftd_Card::TOKEN_STATUS_OK)
            {
                if(self::AddDiscountCoupon($code, array('title'=>$card->card_title, 'amount'=>$card->amount_available, 'owner'=>$card->owner_name)))
                {
                    $q = CCatalogDiscountCoupon::GetList(array(), $arFilter = array("COUPON"=>$code, "ACTIVE"=>"Y"));
                    $coupon = $q->GetNext();
                }
            }
        }

        return $coupon;
    }


    public static function ChargeCouponOnBeforeOrderAdd(&$arFields)
    {
        if(self::Init())
        {
            foreach(CCatalogDiscount::GetCoupons() as $coupon)
            {
                $card = self::GetGiftdCouponCard($coupon);
                if($card && $card->token_status == Giftd_Card::TOKEN_STATUS_OK)
                {
                    try {
                        self::$_client->charge($coupon, $card->amount_available, $arFields['PRICE'], CSaleBasket::GetBasketUserID().'_'.$arFields['PRICE']);
                        break;
                    }
                    catch (Giftd_Exception $e) {
                        // KK: и что тут делать?
                    }
                }
            }
        }
        return true;
    }

}

?>