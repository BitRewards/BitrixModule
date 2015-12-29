<?php

class GiftdDiscountManager
{
    /**
     * @var GiftdClient $_client
     */
    private static $_client = null;
    private static $_module_id = 'giftd.coupon';
    private static $_code_prefix = '';
    public static $last_error = '';

    private static $_basketCount = null;
    private static $_basketCountWithoutDiscounted = null;
    private static $_basketTotalAmountWithoutDiscount = null;
    private static $_basketTotalAmountDiscounted = null;
    private static $_cardCache = array();
    private static $_bitrixCouponCache = array();
    private static $_currentlyActiveCard = false;

    private static $_giftdDiscountAmountLeft = null;

    private static $_lastQuantity = null;

    const STATUS_APPLIED = 'applied';
    const STATUS_FAILED = 'failed';
    const STATUS_UNKNOWN = 'unknown';

    public static $STATUS = self::STATUS_UNKNOWN;
    public static $MESSAGE = null;
    public static $COUPON = null;

    private static function Init()
    {
        if(self::$_client != null)
            return true;

        $api_key = GiftdHelper::GetOption('API_KEY');
        $user_id = GiftdHelper::GetOption('USER_ID');
        self::$_code_prefix = GiftdHelper::GetOption('PARTNER_TOKEN_PREFIX');

        if(strlen($api_key) == 0 || strlen($user_id) == 0)
            return false;

        self::$_client = new GiftdClient($user_id, $api_key);
        return true;
    }

    private static function _useNewCouponSystem()
    {
        return class_exists('\Bitrix\Catalog\DiscountTable') && class_exists('\Bitrix\Sale\DiscountCouponsManager');
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

    /**
     * @param Giftd_Card $card
     */
    private static function _getGiftdDiscountAmountLeft($card)
    {
        if (!$card) {
            return 0;
        }
        if (self::$_giftdDiscountAmountLeft === null) {
            self::$_giftdDiscountAmountLeft = $card->amount_available;
        }

        return self::$_giftdDiscountAmountLeft;
    }

    private static function _subtractFromGiftdDiscountAmount($card, $amount)
    {
        $amountLeft = self::_getGiftdDiscountAmountLeft($card);
        self::$_giftdDiscountAmountLeft = $amountLeft - $amount;
    }

    public static function resetGetOptimalPriceCycle()
    {
        self::$_giftdDiscountAmountLeft = null;
    }

    private static function CreateCoupon($arFields)
    {
        return ((int)CCatalogDiscountCoupon::Add($arFields)) ?: null;
    }

    /**
     * @param $coupon_code
     * @param Giftd_Card $card
     * @return bool|int|null
     */
    private static function AddDiscountCoupon($coupon_code, $card)
    {
        $discountName = 'Giftd-' . ((float)$card->amount_available) . '-' . $card->min_amount_total;

        $q = CCatalogDiscount::GetList(array(), $arFilter = array('NAME' => $discountName, 'ACTIVE' => 'Y', 'SITE_ID' => SITE_ID));
        $discount = $q->GetNext();

        $id_discount = $discount ? $discount['ID'] : 0;

        if ($id_discount == 0) {
            $arDiscountFields = array(
                'ACTIVE' => 'Y',
                'NAME' => $discountName,
                'VALUE_TYPE' => 'F',
                'VALUE' => 0.01,
                'CURRENCY' => 'RUB',
                'NOTES' => 'Скидка, реализующая подарочную карту ' . $discountName,
                'SITE_ID' => SITE_ID,
            );

            $id_discount = self::CreateDiscount($arDiscountFields);
        }

        if ($id_discount > 0) {
            $data = array(
                "DISCOUNT_ID" => $id_discount,
                "ACTIVE" => "Y",
                "ONE_TIME" => "O",
                "COUPON" => $coupon_code,
                "DATE_APPLY" => false,
                'DESCRIPTION' => $card->card_title . '(' . $card->owner_name . ')',
            );

            $existingRow = self::getBitrixCoupon($coupon_code);

            if ($existingRow) {
                CCatalogDiscountCoupon::Update($existingRow['ID'], $data);
                return $existingRow['ID'];
            } else {
                return self::CreateCoupon($data);
            }
        }

        self::$_bitrixCouponCache = array();

        return false;
    }

    public static function looksLikeGiftdToken($code)
    {
        return !(strlen($code) == 0 || self::Init() == false || !strstr($code, self::$_code_prefix));
    }

    /**
     * @param $code
     * @return Giftd_Card
     */
    public static function getGiftdCard($code)
    {
        if (!self::looksLikeGiftdToken($code))
            return null;

        if (!isset(self::$_cardCache[$code])) {
            try {
                self::$_cardCache[$code] = self::$_client->checkByToken($code);
            } catch (Exception $e) {
                self::$_cardCache[$code] = null;
            }
        }

        return self::$_cardCache[$code];
    }

    /**
     * @param $code
     * @return array
     */
    public static function getBitrixCoupon($code)
    {
        if (!trim($code)) {
            return null;
        }
        if (!isset(self::$_bitrixCouponCache[$code])) {
            if (!self::_useNewCouponSystem()) {
                $q = CCatalogDiscount::GetList(array(), array('COUPON' => trim($code)));
                $row = $q->Fetch();
            } else {
                $couponIterator = \Bitrix\Catalog\DiscountCouponTable::getList(array(
                    'filter' => array('=COUPON' => $code)
                ));

                $row = $couponIterator->fetch();
            }

            self::$_bitrixCouponCache[$code] = $row ?: null;
        }

        return self::$_bitrixCouponCache[$code];
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
            $card = self::getGiftdCard($code);
            if($card && $card->token_status == Giftd_Card::TOKEN_STATUS_OK)
            {
                if(self::AddDiscountCoupon($code, $card))
                {
                    $q = CCatalogDiscountCoupon::GetList(array(), $arFilter = array("COUPON"=>$code, "ACTIVE"=>"Y"));
                    $coupon = $q->GetNext();
                }
            }
        }

        return $coupon;
    }

    public static function Charge($coupon, $amount, $amountTotalWithoutGiftdDiscount)
    {
        try {
            self::$_client->charge($coupon, $amount, $amountTotalWithoutGiftdDiscount, CSaleBasket::GetBasketUserID() . '_' . $amountTotalWithoutGiftdDiscount . '_' . date('d.m.Y-H:i:s') . '_' . rand(1, 1 << 30));
            return true;
        }
        catch (Giftd_Exception $e) {
            return false;
        } catch (Giftd_NetworkException $e) {
            return false;
        }
    }

    public static function ChargeCouponOnBeforeOrderAdd(&$arFields)
    {

        if(self::Init())
        {
            $coupons = CCatalogDiscount::GetCoupons();
            if (!$coupons && self::$COUPON) {
                $coupons[] = self::$COUPON;
            }
            /**
             * @var Giftd_Card $maxCard
             */
            $maxCard = null;
            foreach ($coupons as $coupon)
            {
                if ($card = self::getGiftdCard($coupon)) {
                    if (!$maxCard || $maxCard->amount_available < $card->amount_available) {
                        $maxCard = $card;
                    }
                }
            }

            if ($maxCard) {
                $amount = $arFields['PRICE'] + $maxCard->amount_available;
                self::Charge($maxCard->token, $maxCard->amount_available, $amount);
            }
        }
        return true;
    }

    private static function _fillBasketData()
    {
        $q = CSaleBasket::GetList(Array(), Array("FUSER_ID"=>CSaleBasket::GetBasketUserID(), "ORDER_ID"=>false, "CAN_BUY" => "Y", "DELAY" => "N"));
        self::$_basketTotalAmountWithoutDiscount = 0;
        self::$_basketTotalAmountDiscounted = 0;
        self::$_basketCount = 0;
        self::$_basketCountWithoutDiscounted = 0;
        while ($item = $q->GetNext()) {
            self::$_basketTotalAmountWithoutDiscount += ($item["PRICE"] + (isset($item['DISCOUNT_PRICE']) ? $item['DISCOUNT_PRICE'] : 0)) * $item["QUANTITY"];
            self::$_basketTotalAmountDiscounted += $item['PRICE'] * $item['QUANTITY'];
            self::$_basketCount++;
            if (!(isset($item['DISCOUNT_PRICE']) && ($item['DISCOUNT_PRICE'] > 0))) {
                self::$_basketCountWithoutDiscounted++;
            }
        }

    }

    public static function getBasketCount()
    {
        if (self::$_basketCount === null) {
            self::_fillBasketData();
        }
        return self::$_basketCount;
    }

    public static function getBasketCountWithoutDiscounted()
    {
        if (self::$_basketCountWithoutDiscounted === null) {
            self::_fillBasketData();
        }
        return self::$_basketCountWithoutDiscounted;
    }

    public static function getBasketTotalDiscounted()
    {
        if (self::$_basketTotalAmountDiscounted === null) {
            self::_fillBasketData();
        }
        return self::$_basketTotalAmountDiscounted;
    }

    public static function getBasketTotalWithoutDiscount()
    {
        if (self::$_basketTotalAmountWithoutDiscount === null) {
            self::_fillBasketData();
        }
        return self::$_basketTotalAmountWithoutDiscount;
    }

    public static function InvalidateBitrixDiscount($couponId)
    {
        CCatalogDiscountCoupon::Update($couponId, array(
            'ACTIVE' => 'N'
        ));
    }

    public static function getCurrentlyActiveCard()
    {
        if (self::$_currentlyActiveCard !== false) {
            return self::$_currentlyActiveCard;
        }

        $result = null;
        if (self::_useNewCouponSystem()) {
            $coupons = \Bitrix\Sale\DiscountCouponsManager::get();
            $coupons = array_keys($coupons);
        } else {
            $coupons = CCatalogDiscount::GetCoupons();
        }

        /**
         * @var Giftd_Card $result
         */
        $result = null;

        foreach ($coupons as $code) {
            $card = self::getGiftdCard($code);
            if ($card->amount_available) {
                if (!$result || $result->amount_available < $card->amount_available) {
                    $result = $card;
                }
            }
        }

        if ($result) {
            $minAmountTotal = $result->min_amount_total;
            $sessionKey = self::_getSessionKey($result->token);
            if (isset($_SESSION[$sessionKey])) {
                $minAmountTotal -= $result->amount_available;
            } else {
                $_SESSION[$sessionKey] = true;
            }

            if (self::getBasketTotalDiscounted() < $minAmountTotal) {
                self::$STATUS = self::STATUS_FAILED;
                self::$MESSAGE = sprintf("Минимальная сумма заказа для использования подарочной карты составляет %s", ((float)$minAmountTotal) . " руб.");
                $result = null;
            }
        }

        foreach ($coupons as $code) {
            if (!$result || $code != $result->token) {
                unset($_SESSION[self::_getSessionKey($code)]);
            }
        }

        self::$_currentlyActiveCard = $result;

        return $result;
    }

    private static function _getSessionKey($token)
    {
        return 'giftd_card_applied_' . $token;
    }

    public static function AdjustPriceOnGetOptimalPriceResult(&$result)
    {
        $basketAmount = self::getBasketTotalWithoutDiscount();
        $quantity = self::$_lastQuantity;

        if (!$basketAmount || !$quantity) {
            return;
        }

        if (isset($_COOKIE['giftd-debug']) && $_COOKIE['giftd-debug'] == GiftdHelper::getApiKey()) {
            GiftdHelper::debug($result, $quantity);
        }

        try {
            if (isset($result['DISCOUNT_PRICE']) && isset($result['DISCOUNT']['NAME']) && isset($result['PRICE']['PRICE'])) {
                $originalPrice = isset($result['RESULT_PRICE']) ? $result['RESULT_PRICE']['BASE_PRICE'] : $result['PRICE']['PRICE'];
                $bitrixDiscountId = isset($result['DISCOUNT']['ID']) ? $result['DISCOUNT']['ID'] : null;

                $giftdCard = self::getCurrentlyActiveCard();

                $discountAmountLeft = $giftdCard ?
                    min($basketAmount, (float)self::_getGiftdDiscountAmountLeft($giftdCard)) :
                    0;

                $currentDiscountIsGiftd = $giftdCard ?
                    (isset($result['DISCOUNT']['COUPON']) && ($result['DISCOUNT']['COUPON'] == $giftdCard->token)) :
                    false;

                $isAnotherDiscountActive = isset($result['DISCOUNT']['ID']) && !$currentDiscountIsGiftd;

                $giftCardCouldNotBeUsedBecauseOfAnotherDiscount =
                    $giftdCard && $isAnotherDiscountActive && $giftdCard->cannot_be_used_on_discounted_items;

                $giftdCardCouldNotBeUsed =
                    !$giftdCard ||
                    $giftCardCouldNotBeUsedBecauseOfAnotherDiscount ||
                    !$discountAmountLeft;

                if (self::looksLikeGiftdToken($result['DISCOUNT']['COUPON']) && (
                        !$giftdCard || $result['DISCOUNT']['COUPON'] != $giftdCard->token || $giftdCardCouldNotBeUsed
                    )) {
                    unset($result['DISCOUNT']);
                }

                for ($i = 0; $i < count($result['DISCOUNT_LIST']); $i++) {
                    $currentToken = $result['DISCOUNT_LIST'][$i]['COUPON'];
                    if (self::looksLikeGiftdToken($currentToken)) {
                        if (!$giftdCard || $currentToken != $giftdCard->token || $giftdCardCouldNotBeUsed) {
                            $badDiscountValue = $result['DISCOUNT_LIST'][$i]['VALUE'];
                            $result['DISCOUNT_PRICE'] += $badDiscountValue;
                            if (isset($result['RESULT_PRICE'])) {
                                $result['RESULT_PRICE']['DISCOUNT_PRICE'] += $badDiscountValue;
                                $result['RESULT_PRICE']['DISCOUNT'] = max($result['RESULT_PRICE']['DISCOUNT'] - $badDiscountValue, 0);
                            }
                            array_splice($result['DISCOUNT_LIST'], $i--, 1);
                        }
                    }
                }


                if (!$giftdCard || $giftdCardCouldNotBeUsed || !$discountAmountLeft) {
                    if ($giftCardCouldNotBeUsedBecauseOfAnotherDiscount) {
                        if (self::$STATUS != self::STATUS_APPLIED) {
                            self::$STATUS = self::STATUS_FAILED;
                            self::$MESSAGE = "Подарочная карта не может быть использована с товарами, продающимися со скидкой";
                        }
                    }

                    return true;
                }


                $discountBasePrice =
                    ($currentDiscountIsGiftd || $giftdCard->cannot_be_used_on_discounted_items) ?
                        $originalPrice :
                        $result['DISCOUNT_PRICE'];

                if ($giftdCard->cannot_be_used_on_discounted_items || !$giftdCard->is_discount_divided_equally) {
                    $currentDiscountAmount = $discountAmountLeft;
                } else {
                    $currentPriceProportion = ($discountBasePrice * $quantity) / $basketAmount;
                    $currentDiscountAmount = $currentPriceProportion * $giftdCard->amount_available;
                }

                $currentDiscountAmount = min($currentDiscountAmount, $discountAmountLeft, $discountBasePrice * $quantity);

                $singleItemDiscountValue = round(($currentDiscountAmount / $quantity) * 100) / 100;
                $priceAfterDiscount = $discountBasePrice - $singleItemDiscountValue;

                if ($priceAfterDiscount > $result['DISCOUNT_PRICE']) {
                    return true;
                }

                self::_subtractFromGiftdDiscountAmount($giftdCard, $currentDiscountAmount);

                $discountAmount = ($originalPrice - $priceAfterDiscount);

                $result['DISCOUNT_PRICE'] = $priceAfterDiscount;
                $result['DISCOUNT']['DISCOUNT_CONVERT'] = $discountAmount;
                $result['DISCOUNT']['VALUE'] = $discountAmount;
                if (isset($result['RESULT_PRICE'])) {
                    $result['RESULT_PRICE']['DISCOUNT_PRICE'] = $priceAfterDiscount;
                    $result['RESULT_PRICE']['DISCOUNT'] = $discountAmount;
                }

                $updatedDiscountList = array();

                foreach ($result['DISCOUNT_LIST'] as &$discountItem) {
                    if (stripos($discountItem['NAME'], 'giftd') === false) {
                        $updatedDiscountList[] = $discountItem;
                    }
                }

                $giftdBitrixCoupon = self::getBitrixCoupon($giftdCard->token);
                $giftdBitrixCoupon['VALUE'] = $singleItemDiscountValue;
                $giftdBitrixCoupon['DISCOUNT_CONVERT'] = $singleItemDiscountValue;

                $updatedDiscountList[] = $giftdBitrixCoupon;

                $result['DISCOUNT_LIST'] = $updatedDiscountList;

                if (!defined('GIFTD_COUPON_APPLIED')) {
                    define('GIFTD_COUPON_APPLIED', true);
                }
                self::$STATUS = self::STATUS_APPLIED;
                self::$MESSAGE = "Подарочная карта успешно применена";
                self::$COUPON = $giftdCard->token;
            }
        } catch (Exception $e) {
            GiftdHelper::debug($e->getMessage(), $e->getTraceAsString());

            throw $e;
        }

    }

    public static function AdjustPriceOnGetOptimalPrice($intProductID, $quantity = 1, $arUserGroups = array(), $renewal = "N", $arPrices = array(), $siteID = false, $arDiscountCoupons = false)
    {
        self::$_lastQuantity = $quantity;

        return true;
    }

    public static function OnBuildCouponProviders($event)
    {
        $result = new Bitrix\Main\EventResult(
            Bitrix\Main\EventResult::SUCCESS,
            array(
                'mode' => Bitrix\Sale\DiscountCouponsManager::COUPON_MODE_SIMPLE,
                'getData' => array('GiftdDiscountManager', 'CouponProviderGetData'),
                'isExist' => array('GiftdDiscountManager', 'CouponProviderIsExist'),
                'saveApplied' => array('GiftdDiscountManager', 'CouponProviderSaveApplied'),
            ),
            self::$_module_id
        );
        return $result;
    }

    private static function _handleCouponProviderCalls($coupon)
    {
        $card = GiftdDiscountManager::getGiftdCard($coupon);

        if ($card &&
            $card->token_status == Giftd_Card::TOKEN_STATUS_OK &&
            $card->min_amount_total <= self::getBasketTotalWithoutDiscount()
        ) {
            $id = self::AddDiscountCoupon($coupon, $card);
            return self::getBitrixCoupon($coupon);
        }

        return null;
    }


    public static function CouponProviderGetData($coupon)
    {
        $card = GiftdDiscountManager::getGiftdCard($coupon);

        if ($card &&
            $card->token_status == Giftd_Card::TOKEN_STATUS_OK
        ) {
            $id = self::AddDiscountCoupon($coupon, $card);
            if (self::_useNewCouponSystem()) {
                $data = self::getBitrixCoupon($coupon);

                if ($data) {
                    $couponValid = self::getBasketTotalDiscounted() >= $card->min_amount_total;

                    $data['CHECK_CODE'] =
                        $couponValid ?
                            0x0000 : // DiscountCouponsManager::COUPON_CHECK_OK
                            0x0800; // DiscountCouponsManager::COUPON_CHECK_NOT_APPLIED

                    $data['STATUS'] =
                        $couponValid ?
                            0x0004 : // DiscountCouponsManager::STATUS_APPLYED
                            0x0008; // DiscountCouponsManager::STATUS_NOT_APPLYED

                    $data['TYPE'] = 0x0002;
                    $data['DISCOUNT_ACTIVE'] = 'Y';
                }
            } else {
                $data = CCatalogDiscountCoupon::GetList(array(), array('ID' => $id))->Fetch();
            }

            return $data;
        }

        return null;
    }

    public static function CouponProviderIsExist($coupon)
    {
        return ;// self::_handleCouponProviderCalls($coupon);
    }

    public static function CouponProviderSaveApplied($appliedCoupons, $userId, $currentTime)
    {
        return array();
        // this function should not be called or used
        // left here just in case of something

        $result = array();

        foreach ($appliedCoupons as $coupon) {
            if ($card = self::getGiftdCard($coupon)) {
                $amountTotal = self::getBasketTotalDiscounted() + $card->amount_available;
                if (self::Charge($coupon, $card->amount_available, $amountTotal)) {

                } else {

                }
            }
        }

        return $result;
    }

}


