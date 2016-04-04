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

    /**
     * @var $_lastGiftdCard Giftd_Card
     */
    private static $_lastGiftdCard = null;

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
        static $result = null;

        if ($result === null) {
            $result = class_exists('\Bitrix\Sale\Internals\DiscountCouponTable') && class_exists('\Bitrix\Sale\DiscountCouponsManager') && !GiftdHelper::GetOption('DISABLE_BASKET_RULES');
        }

        return $result;
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


    /**
     * @param $discountName
     * @param $couponCode
     * @param Giftd_Card $card
     * @return bool|null
     */
    private static function _addDiscountCouponBasketRules($discountName, $couponCode, $card)
    {
        $q = CSaleDiscount::GetList(array(), $arFilter = array('NAME' => $discountName, 'SITE_ID' => SITE_ID));
        $discount = $q->GetNext();

        $id_discount = $discount ? $discount['ID'] : 0;

        $groupIterator = \Bitrix\Main\GroupTable::getList(array('select' => array('ID')));
        $groupIds = array();
        while ($group = $groupIterator->fetch()) {
            $groupIds[] = $group["ID"];
        }

        $arDiscountFields = array(
            'ACTIVE' => 'N',
            'NAME' => $discountName,
            'DISCOUNT_TYPE' => 'V',
            'DISCOUNT_VALUE' => (float)$card->amount_available,
            'PRICE_FROM' => $card->min_amount_total ?: null,
            'CURRENCY' => 'RUB',
            'NOTES' => 'Скидка, реализующая подарочную карту ' . $discountName,
            'SITE_ID' => SITE_ID,
            'LID' => SITE_ID,
            'USER_GROUPS' => $groupIds,
            'PRIORITY' => $card->min_amount_total ? 1 : 100,
            'USE_COUPONS' => 'Y',
        );

        if ($id_discount == 0) {
            $id_discount = CSaleDiscount::Add($arDiscountFields);
        } else {
            unset($arDiscountFields['USER_GROUPS']);
            CSaleDiscount::Update($id_discount, $arDiscountFields);
        }

        $discountCouponId = false;

        if ($id_discount > 0) {
            $activeTo = $card->expires ? Bitrix\Main\Type\DateTime::createFromTimestamp($card->expires) : null;

            $data = array(
                "DISCOUNT_ID" => $id_discount,
                "ACTIVE" => "Y",
                "ACTIVE_TO" => $activeTo,
                "TYPE" => \Bitrix\Sale\Internals\DiscountCouponTable::TYPE_ONE_ORDER,
                "MAX_USE" => 1,
                "COUPON" => $couponCode,
                'DESCRIPTION' => $card->card_title . '(' . $card->owner_name . ')',
            );

            $existingRow = self::getBitrixCoupon($couponCode);

            if ($existingRow['DISCOUNT_ID'] != $id_discount) {
                CSaleDiscount::Update($existingRow['DISCOUNT_ID'], array('ACTIVE' => 'N'));
            }

            if ($existingRow) {
                \Bitrix\Sale\Internals\DiscountCouponTable::Update($existingRow['ID'], $data);
                $discountCouponId = $existingRow['ID'];
            } else {
                $discountCouponId = ((int)\Bitrix\Sale\Internals\DiscountCouponTable::Add($data)) ?: null;
            }

            if ($discountCouponId) {
                CSaleDiscount::Update($id_discount, array('ACTIVE' => 'Y'));
            }
        }

        self::$_bitrixCouponCache = array();

        return $discountCouponId;
    }

    /**
     * @param $coupon_code
     * @param Giftd_Card $card
     * @return bool|int|null
     */
    private static function AddDiscountCoupon($coupon_code, $card)
    {
        $discountName = 'Giftd-' . ((float)$card->amount_available) . '-' . $card->min_amount_total;

        if (self::_useNewCouponSystem()) {
            return self::_addDiscountCouponBasketRules($discountName, $coupon_code, $card);
        }

        $arDiscountFields = array(
            'ACTIVE' => 'N',
            'NAME' => $discountName,
            'VALUE_TYPE' => 'F',
            'VALUE' => 0.01,
            'CURRENCY' => 'RUB',
            'NOTES' => 'Скидка, реализующая подарочную карту ' . $discountName,
            'SITE_ID' => SITE_ID,
        );

        $q = CCatalogDiscount::GetList(array(), $arFilter = array('NAME' => $discountName, 'SITE_ID' => SITE_ID));
        $discount = $q->GetNext();

        $id_discount = $discount ? $discount['ID'] : 0;

        if ($id_discount == 0) {
            $id_discount = self::CreateDiscount($arDiscountFields);
        } else {
            CCatalogDiscount::Update($id_discount, $arDiscountFields);
        }

        $discountCouponId = false;

        if ($id_discount > 0) {
            $data = array(
                "DISCOUNT_ID" => $id_discount,
                "ACTIVE" => "Y",
                "ONE_TIME" => "O",
                "COUPON" => $coupon_code,
                "DATE_APPLY" => false,
                'DESCRIPTION' => $card->card_title . ($card->owner_name ? (' (' . $card->owner_name . ')') : ''),
            );

            $existingRow = self::getBitrixCoupon($coupon_code);

            if ($existingRow['DISCOUNT_ID'] != $id_discount) {
                CCatalogDiscount::Update($existingRow['DISCOUNT_ID'], array('ACTIVE' => 'N'));
            }

            if ($existingRow) {
                CCatalogDiscountCoupon::Update($existingRow['ID'], $data);
                $discountCouponId = $existingRow['ID'];
            } else {
                $discountCouponId = ((int)CCatalogDiscountCoupon::Add($data)) ?: null;
            }

            if ($discountCouponId) {
                CCatalogDiscount::Update($id_discount, array('ACTIVE' => 'Y'));
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
    public static function getGiftdCard($code, $amountTotal = null)
    {
        if (!self::looksLikeGiftdToken($code))
            return null;

        if (!$amountTotal) {
            $amountTotal = self::getBasketTotalDiscounted();
        }

        if (!isset(self::$_cardCache[$code])) {
            try {
                self::$_cardCache[$code] = self::$_client->checkByToken($code, $amountTotal);
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
                $couponIterator = \Bitrix\Sale\Internals\DiscountCouponTable::getList(array(
                    'select' => array(
                        'ID', 'COUPON', 'DISCOUNT_ID', 'TYPE', 'ACTIVE',
                        'USER_ID', 'MAX_USE', 'USE_COUNT', 'ACTIVE_FROM', 'ACTIVE_TO',
                        'DISCOUNT_NAME' => 'DISCOUNT.NAME', 'DISCOUNT_ACTIVE' => 'DISCOUNT.ACTIVE',
                        'DISCOUNT_ACTIVE_FROM' => 'DISCOUNT.ACTIVE_FROM', 'DISCOUNT_ACTIVE_TO' => 'DISCOUNT.ACTIVE_TO'
                    ),
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

    public static function Charge($coupon, $amount, $amountTotalWithoutGiftdDiscount, $id)
    {
        try {
            self::$_client->charge($coupon, $amount, $amountTotalWithoutGiftdDiscount, $id);
            return true;
        }
        catch (Giftd_Exception $e) {
            return false;
        } catch (Giftd_NetworkException $e) {
            return false;
        }
    }

    public static function getCouponList()
    {
        if (self::_useNewCouponSystem()) {
            $coupons = \Bitrix\Sale\DiscountCouponsManager::get();
            if ($coupons) {
                $coupons = array_keys($coupons);
            }
        } else {
            $coupons = CCatalogDiscount::GetCoupons();
        }

        if (!$coupons) {
            $coupons = array();
        }

        if (isset($_SESSION['CATALOG_USER_COUPONS'])) {
            foreach ($_SESSION['CATALOG_USER_COUPONS'] as $coupon) {
                $coupons[] = $coupon;
            }
        }

        $coupons = array_unique($coupons);

        return $coupons;
    }

    public static function ChargeCouponOnBeforeOrderAdd(&$arFields)
    {
        if(self::Init())
        {
            $coupons = self::getCouponList();
            if (!$coupons && self::$COUPON) {
                $coupons[] = self::$COUPON;
            }

            $amountTotal = $arFields['PRICE'];
            foreach ($coupons as $coupon)
            {
                if ($card = self::getGiftdCard($coupon, $amountTotal)) {
                    $amount = $amountTotal + $card->amount_available;
                    if ($result = self::Charge($coupon, $card->amount_available, $amount, date('dmYhis') . '_' . mt_rand(1, 1 << 30))) {
                        $arFields['COMMENTS'] = self::getOrderComment($card);
                        break;
                    }
                    self::$_lastGiftdCard = $card;
                }
            }
        }
        return true;
    }

    private static function getOrderComment(Giftd_Card $card)
    {
        $cardTitle = $card->min_amount_total ? "Giftd promo card" : "Giftd paid card";
        $cardTitle .= " (discount " . ((float)$card->amount_available);
        if ($card->min_amount_total) {
            $cardTitle .= ", min. order " . ((float)$card->min_amount_total);
        }
        $cardTitle .= ')';

        return "$cardTitle applied // " . $card->token;
    }

    private static function getTokenByOrderComment($comment)
    {
        if (strpos($comment, 'Giftd') !== 0) {
            return null;
        }

        $parts = explode("//", $comment);

        if (count($parts) != 2) {
            return null;
        }

        return trim($parts[1]);
    }

    public static function UpdateExternalIdAfterOrderSave($orderId, $arFields)
    {
        if (!isset($arFields['COMMENTS'])) {
            return null;
        }

        if (!($token = self::getTokenByOrderComment($arFields['COMMENTS']))) {
            return null;
        }

        try {
            GiftdHelper::QueryApi('gift/updateExternalId', array(
                'token' => $token,
                'external_id' => $orderId
            ));
        } catch (Exception $e) {

        }
    }

    public static function ChargeCouponOnOrderSave($orderId, $arFields, $arOrder, $isNew)
    {
        self::UpdateExternalIdAfterOrderSave($orderId, $arFields);

        return;

        if (self::IsDebugMode()) {
            GiftdHelper::debug("Handling OnOrderSave", func_get_args());
        }

        if ($isNew && self::Init())
        {
            $discounts = isset($arOrder['DISCOUNT_LIST']) ? $arOrder['DISCOUNT_LIST'] : array();
            $card = null;
            foreach ($discounts as $arDiscount) {
                $code = isset($arDiscount['COUPON']['COUPON']) ? $arDiscount['COUPON']['COUPON'] : null;
                if ($code) {
                    $card = self::getGiftdCard($code);
                }
            }

            if ($card) {
                $amount = $arFields['PRICE'] + $card->amount_available;
                if ($chargeResult = self::Charge($card->token, $card->amount_available, $amount, $orderId)) {
                    CSaleOrder::Update($orderId, array('COMMENTS' => self::getOrderComment($card)));
                }

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

    public static function getCurrentlyActiveCard($checkMinAmountTotal = true)
    {
        if (self::$_currentlyActiveCard !== false) {
            return self::$_currentlyActiveCard;
        }

        $result = null;
        $coupons = self::getCouponList();

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

        if ($result && $checkMinAmountTotal) {
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

        if ($checkMinAmountTotal) {
            self::$_currentlyActiveCard = $result;
        }


        return $result;
    }

    private static function _getSessionKey($token)
    {
        return 'giftd_card_applied_' . $token;
    }

    private static function IsDebugMode()
    {
        return isset($_COOKIE['giftd-debug']) && $_COOKIE['giftd-debug'] == GiftdHelper::getApiKey();
    }

    public static function AdjustPriceOnGetOptimalPriceResult(&$result)
    {
        self::UpdateCurrentlyActiveCard();

        if (self::_useNewCouponSystem()) {
            return;
        }

        $basketAmount = self::getBasketTotalWithoutDiscount();
        $quantity = self::$_lastQuantity;

        if (!$basketAmount || !$quantity) {
            return;
        }

        if (self::IsDebugMode()) {
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
                'mode' => Bitrix\Sale\DiscountCouponsManager::COUPON_MODE_FULL,
                'getData' => array('GiftdDiscountManager', 'CouponProviderGetData'),
                'isExist' => array('GiftdDiscountManager', 'CouponProviderIsExist'),
                'saveApplied' => array('GiftdDiscountManager', 'CouponProviderSaveApplied'),
            ),
            'sale'
        );
        return $result;
    }

    public static function CouponProviderIsExist($coupon)
    {
        return static::getBitrixCoupon($coupon);
    }

    public static function UpdateCurrentlyActiveCard()
    {
        static $updated = false;

        if ($updated) {
            return;
        }

        $card = self::getCurrentlyActiveCard(false);
        if ($card) {
            self::AddDiscountCoupon($card->token, $card);
        }

        $updated = true;
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
                }

            } else {
                $data = CCatalogDiscountCoupon::GetList(array(), array('ID' => $id))->Fetch();
            }

            return $data;
        }

        return null;
    }

    public static function CouponProviderSaveApplied($appliedCoupons, $userId, $currentTime)
    {
        return array();
    }

    public static function RestrictGiftdCouponDelete($data)
    {
        if (is_scalar($data) && is_numeric($data)) {
            $discountCouponId = $data;
        } else {
            if (!isset($data['ID'])) {
                return;
            }
            $discountCouponId = (int)$data['ID'];
        }

        global $DB;

        if (self::_useNewCouponSystem()) {
            $dbResult = $DB->Query("SELECT DISCOUNT_ID FROM b_sale_discount_coupon WHERE ID = $discountCouponId");
            $row = $dbResult->Fetch();
            if ($row && $discountId = $row['DISCOUNT_ID']) {
                $discount = \Bitrix\Sale\Internals\DiscountTable::getById($discountId);
                if ($data = $discount->fetch()) {
                    if (isset($data['NAME']) && strpos($data['NAME'], 'Giftd') !== false) {
                        die("Удаление купонов Giftd не рекомендовано, т.к. может привести к тому, что скидка на корзину Giftd будет применяться ко всем заказам без исключения");
                    }
                }
            }
        } else {
            $dbResult = $DB->Query("SELECT DISCOUNT_ID FROM b_catalog_discount_coupon WHERE ID = $discountCouponId");
            $row = $dbResult->Fetch();
            if ($row && $discountId = $row['DISCOUNT_ID']) {
                $discount = CCatalogDiscount::GetList(array('ID' => $discountId));
                if ($data = $discount->fetch()) {
                    if (isset($data['NAME']) && strpos($data['NAME'], 'Giftd') !== false) {
                        die("Удаление купонов Giftd не рекомендовано, т.к. может привести к тому, что скидка на корзину Giftd будет применяться ко всем заказам без исключения");
                    }
                }
            }
        }
    }

    public static function FixUseCouponsFlagAfterDelete()
    {
        global $DB;
        try {
            $result = $DB->Update("b_sale_discount", array('USE_COUPONS' => "'Y'"), "WHERE NAME LIKE 'Giftd%'");
        } catch (Exception $e) {
            $result = false;
        }

        if ($result) {
            die("Удаление купонов Giftd не рекомендовано, т.к. может привести к тому, что скидка на корзину Giftd будет применяться ко всем заказам без исключения");
        }
        static $registered = false;
        if (!$registered) {
            $registered = true;
            \Bitrix\Main\EventManager::getInstance()->addEventHandler('main', 'OnAfterEpilog', array('GiftdDiscountManager', 'FixUseCouponsFlagAfterDelete'));
        }
    }

}


