<?php
require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');

global $USER, $APPLICATION;

$module_id = 'giftd.coupon';
if (!CModule::IncludeModule('catalog') || !CModule::IncludeModule($module_id))
    return;

if(!GiftdHelper::IsComponentActive())
    return;

CUtil::JSPostUnescape();

$coupon = $_POST['coupon'] ? trim($_POST['coupon']) : '';
$result = '';
$msg ='';

if(strlen($coupon) > 0)
{
    $result = GiftdDiscountManager::SetCoupon($coupon);
    $msg = GiftdDiscountManager::$last_error;
}

$arRes = array('status' => ($result ? 'valid' : 'invalid'), 'msg'=>$msg);

header('Content-Type: application/json; charset='.LANG_CHARSET);
echo CUtil::PhpToJSObject($arRes);
?>