<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
    die();

$module_id = 'giftd.coupon';
if (!CModule::IncludeModule($module_id))
    return;
?>

<?if(GiftdHelper::IsComponentActive()):

    $type = GiftdHelper::ComponentType();
    $coupon_filed_id = GiftdHelper::ComponentJSCouponFieldID();
    $coupon_validation_callback = GiftdHelper::ComponentJSCouponValidationCallback();

    if(!$coupon_filed_id)
        $coupon_filed_id = 'giftd_coupon';

    $ajax_rul = BX_ROOT.'/components/giftd/coupon.input/ajax.php';
    ?>

    <?if($type == 'INPUT'):?>
    <input type="text" name="coupon" id="<?=$coupon_filed_id?>">
<?endif;?>

    <?if($type <> ''):?>
    <script type="text/javascript">

        <?if($coupon_validation_callback <> ''):?>
        var validation_callback = '<?$coupon_validation_callback?>';
        <?endif;?>

        function validate_coupon_giftd(){
            BX.ajax({
                url: '<?=$ajax_rul?>',
                data: {'coupon': this.value},
                method: 'POST',
                dataType: 'json',
                timeout: 30,
                async: true,
                processData: true,
                scriptsRunFirst: true,
                emulateOnload: true,
                start: true,
                cache: false,
                onsuccess: function(data){
                    console.log(data);

                    if(typeof window[validation_callback] === 'function')
                    {
                        var callback = window[validation_callback];
                        callback(data);
                    }
                },
                onfailure: function(data){
                    console.log(data);
                }
            });
        }

        var coupon_field = document.getElementById('<?=$coupon_filed_id?>');
        if(coupon_field)
        {
            coupon_field.onchange = validate_coupon_giftd;
        }

    </script>
<?endif;?>

<?endif;?>

