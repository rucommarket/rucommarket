<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddChainItem('Оплата заказа',$arParams['SEF_FOLDER'].$arParams['SEF_URL_TEMPLATES']['payment']);

$this->setFrameMode(false);
?>
<a href="<?=$arParams['SEF_FOLDER']?><?=$arParams['SEF_URL_TEMPLATES']['payment']?>?ORDER_ID=<?=$_REQUEST['ORDER_ID']?>&PAYMENT_ID=<?=$_REQUEST['PAYMENT_ID']?>" id="pay_link" onclick="void(0);">
    Переход на оплату...
</a>
<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddChainItem('Оплата заказа',$arParams['SEF_FOLDER'].$arParams['SEF_URL_TEMPLATES']['payment']);

$this->setFrameMode(false);
\CJSCore::Init(["countdown"]);
$countdownTime = FormatDate("Y/m/d H:i:s", MakeTimeStamp($arResult['ORDER']['DATE_INSERT']) + 90 * 60);
?>
<style>
    .header {
        display: none;
    }
    .footer {
        display: none;
    }
    .skin > .breadcrumbs {
        display: none;
    }
    .innerpage-wrapper .page-content {
        padding-top: 0;
        padding-bottom: 0;
    }
    .innerpage-wrapper .wrapper__push {
        height: 0;
    }
</style>
<section class="checkout-payment<?if($arResult['IS_MOBILE']) echo ' mobile';?>">
	<div class="checkout-payment_pay">
        <a href="/">
            <img src="<?=SITE_TEMPLATE_PATH?>/img/logo.png" alt="Профессиональная косметика на сайте Authentica" title="Интернет-магазин профессиональной косметики Authentica.love" />
        </a>
        <p></p>
    	<h1 class="checkout-header">Оплата заказа</h1>
		<div class="checkout-payment_description">
			<b>Ваше номер заказа №<?=$_REQUEST['ORDER_ID'];?></b>
			<span>
				Для завершения оформления оплатите заказ в течение 90 минут, по истечении этого времени заказ будет аннулирован
			</span>
			<i id="getting-started"></i>
		</div>
        <a class="btn btn-pay_link<?if($arResult['IS_MOBILE']) echo ' mobile';?>" href="<?=$arParams['SEF_FOLDER']?><?=$arParams['SEF_URL_TEMPLATES']['payment']?>?ORDER_ID=<?=$_REQUEST['ORDER_ID']?>&PAYMENT_ID=<?=$_REQUEST['PAYMENT_ID']?>" id="pay_link" onclick="void(0);">
            Переход на оплату
        </a>
	</div>
	<div class="checkout-payment_right">
		<div class="checkout-order_cart">
            <div class="checkout-order_cart-title">
                <h2>Ваш заказ</h2>
                <span>
                    <?=count($arResult['BASKET']['ITEMS'])?>
                    <?if(count($arResult['BASKET']['ITEMS']) == 1) echo "товар";
                    elseif(count($arResult['BASKET']['ITEMS']) > 1 && count($arResult['BASKET']['ITEMS']) < 5) echo "товара";
                    elseif(count($arResult['BASKET']['ITEMS']) >= 5) echo "товаров";?>
                </span>
            </div>
            <?include('include/order/basket.php');?>
            <div class="checkout-order_total">
                <div class="checkout-order_total-row">
                    <span>Товаров на сумму</span>
                    <span class="right"><?=rtrim(rtrim(number_format(($arResult['BASKET']['FULL_PRICE']),2,'.',' '),'0'),'.')?> ₽</span>
                </div>
                <div class="checkout-order_total-row">
                    <span>Доставка</span>
                    <span class="right"><?=rtrim(rtrim(number_format(($arResult['ORDER']['DELIVERY_PRICE']),2,'.',' '),'0'),'.')?> ₽</span>
                </div>
                <div class="checkout-order_total-row">
                    <span>Выгода на товары</span>
                    <span class="right red">- <?=rtrim(rtrim(number_format(($arResult['BASKET']['FULL_PRICE'] - $arResult['BASKET']['PRICE']),2,'.',' '),'0'),'.')?> ₽</span>
                </div>
                <div class="checkout-order_total-row">
                    <span>Оплачено подарочной картой</span>
                    <span class="right red">- <?=rtrim(rtrim(number_format((min($arResult['DIGIFT']['BALANCE_AMOUNT'],$arResult['ORDER']['PRICE'])),2,'.',' '),'0'),'.')?> ₽</span>
                </div>
            </div>
            <div class="checkout-order_next<?if($arResult['IS_MOBILE']) echo ' mobile';?>">
                <div class="checkout-order_next-total">
                    <div class="checkout-order_next-total_text">всего к оплате:</div>
                    <div class="checkout-order_next-total_price">
                        <?if(isset($arResult['DIGIFT']['BALANCE_AMOUNT'])):?>
                            <?if($arResult['ORDER']['PRICE'] > $arResult['DIGIFT']['BALANCE_AMOUNT']) {?>
                                <?=rtrim(rtrim(number_format(($arResult['ORDER']['PRICE'] - $arResult['DIGIFT']['BALANCE_AMOUNT']),2,'.',' '),'0'),'.')?> ₽
                            <?}else {?>
                                0 ₽
                            <?}?>
                        <?else:?>
                        <?=rtrim(rtrim(number_format(($arResult['ORDER']['PRICE']),2,'.',' '),'0'),'.')?> ₽
                        <?endif;?>
                    </div>
                </div>
            </div>
            <?if($arResult['IS_MOBILE']):?>
                <a class="btn btn-pay_link mobile" href="<?=$arParams['SEF_FOLDER']?><?=$arParams['SEF_URL_TEMPLATES']['payment']?>?ORDER_ID=<?=$_REQUEST['ORDER_ID']?>&PAYMENT_ID=<?=$_REQUEST['PAYMENT_ID']?>" id="pay_link" onclick="void(0);">
                    Переход на оплату
                </a>
            <?endif;?>
        </div>
	</div>
</section>
<script>
    BX.ready(function(){
        $("#getting-started")
            .countdown("<?= $countdownTime ?>", function(event) {
                $(this).text(
                    event.strftime('%H:%M:%S')
                );
            }).on('finish.countdown', function(event) {
                location.href = 'order_expired.php'

            });
    });
</script>