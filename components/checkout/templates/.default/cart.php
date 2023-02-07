<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(false);
\CJSCore::Init(["ajax","popup","jquery"]);

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;

global $USER;
?>

<?if(is_array($arResult["COLUMN_LIST"]) && !empty($arResult["COLUMN_LIST"])):?>
<style>
    section.checkout .checkout-cart .checkout-cart_container .checkout-cart_row {
        grid-template-columns: 45% repeat(<?=(count($arResult["COLUMN_LIST"]) - 1)?>,1fr);
    }
    @media screen and (max-width: 688px) {
        #carrotquest-messenger-collapsed-container.carrotquest-messenger-right_bottom {
            bottom: 90px !important;
        }
    }
</style>
<?endif;?>

<section class="checkout<?if($arResult['IS_MOBILE']) echo ' mobile';?>">
    <h1 class="checkout-header">
        <?= Loc::getMessage('YOUR_CART') ?>
        <?if(is_countable($arResult['BASKET']['ITEMS']) && count($arResult['BASKET']['ITEMS']) > 0):?>
        <span>
            <?=count($arResult['BASKET']['ITEMS'])?>
        </span>
        <?endif;?>
    </h1>
    <div class="checkout-cart" id="checkout-cart">
        <?if($arResult['BASKET']['ITEMS']):?>
            <?if(!empty($arResult["COLUMN_LIST"])) {?>
            <div class="checkout-cart_block">
            <div class="checkout-cart_container">
                <div class="checkout-cart_row checkout-cart_header">
                    <?foreach($arResult["COLUMN_LIST"] as $idColumn):?>
                        <div class="checkout-cart__column checkout-cart_title" id="checkout-cart_col_<?=$idColumn?>">
                            <?= Loc::getMessage('RCM_CHECKOUT_CART_COLUMN_LIST_'.$idColumn) ?>
                        </div>
                    <?endforeach?>
                </div>
                <?foreach($arResult['BASKET']['ITEMS'] as $item):?>
                    <div class="checkout-cart_row checkout-cart_item" id="checkout-cart_row-basket-<?=$item['ID']?>" data-basket-id="<?=$item['ID']?>">
                        <?if(!$item['CAN_BUY']):?>
                            <div class="cart-item_can_buy">
                                ОТСУТСТВУЕТ
                            </div>
                        <?endif;?>
                        <?if(in_array("NAME",$arResult["COLUMN_LIST"])){?>
                            <div class="checkout-cart__column cart-item_name">
                                <div class="cart-item_img">
                                    <?if(!empty($arResult['BASKET_ITEMS_PHOTOS'][$item['PRODUCT_ID']])){?>
                                        <a href="<?=$item['PRODUCT']['URL'];?>"
                                            class="cart-product__image"
                                            style="background-image: url('<?=$arResult['BASKET_ITEMS_PHOTOS'][$item['PRODUCT_ID']]?>')"></a>
                                    <?}?>
                                </div>
                                <div class="cart-item_description">
                                    <a href="<?=$item['PRODUCT']['URL'];?>" class="cart-item__title"><?=$item['PRODUCT']['NAME']?></a>
                                    <?foreach($arParams['PRODUCT_PROPERTIES'] as $property):?>
                                        <div class="cart-item_prop">
                                            <span class="cart-item_prop-title"><?=$item['PROPERTIES'][$property]['NAME'];?>:</span>&nbsp;
                                            <span><?=$item['PROPERTIES'][$property]['VALUE'];?></span>
                                        </div>
                                    <?endforeach;?>
                                </div>
                            </div>
                        <?}?>
                        <?if(in_array("PRICE",$arResult["COLUMN_LIST"])){?>
                            <div class="checkout-cart__column cart-item_price<?
                                if($item['BASE_PRICE'] != $item['PRICE'])
                                echo " cart-item_price_new";
                                ?>">
                                <?if(!$item['PROPERTIES']['IS_GIFT'] || $item['PROPERTIES']['IS_GIFT']['VALUE'] != 'Y'){?>
                                    <?=rtrim(rtrim(number_format($item['PRICE'],2,'.',' '),'0'),'.')?> ₽
                                    <?if($item['BASE_PRICE'] != $item['PRICE']):?>
                                        <span><?=rtrim(rtrim(number_format($item['BASE_PRICE'],2,'.',' '),'0'),'.')?> ₽</span>
                                        <span class="cart-item_price_benefit">Выгода -<?=rtrim(rtrim(number_format(($item['BASE_PRICE'] - $item['PRICE']),2,'.',' '),'0'),'.')?> ₽</span>
                                    <?endif;?>
                                <?} else {?>
                                    <span class="is_gift">Подарок</span>
                                <?}?>
                                <?if($item['QUANTITY']!=$arResult['BASKET']['OLD_QUANTITY'][$item['ID']]){?>
                                    <span class="is_quantity">кол-во изменилось</span>
                                <?}?>
                            </div>
                        <?}?>
                        <?if(in_array("QUANTITY",$arResult["COLUMN_LIST"])){?>
                            <div class="checkout-cart__column cart-item_quantity">
                                <?if(!$item['PROPERTIES']['IS_GIFT'] || $item['PROPERTIES']['IS_GIFT']['VALUE'] != 'Y'){?>
                                    <span class="cart-quantity_btn cart-quantity_before" data-event="Down" data-id="<?=$item['ID']?>">
                                        <svg width="7" height="1" viewBox="0 0 7 1" fill="#000000" xmlns="http://www.w3.org/2000/svg">
                                            <rect x="7" width="1" height="7" transform="rotate(90 7 0)"/>
                                        </svg>
                                    </span>
                                    <input type="number" min="1" max="100" class="cart-quantity" data-id="<?=$item['ID']?>" value="<?=$item['QUANTITY']?>">
                                    <span class="cart-quantity_btn cart-quantity_after"  data-event="Up" data-id="<?=$item['ID']?>">
                                        <svg width="7" height="7" viewBox="0 0 7 7" fill="#000000" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M4 0H3V3H0V4H3V7H4V4H7V3H4V0Z"/>
                                        </svg>
                                    </span>
                                <?} else {?>
                                    <input type="number" min="1" max="100" class="cart-quantity" data-id="<?=$item['ID']?>" value="<?=$item['QUANTITY']?>" readonly>
                                <?}?>
                            </div>
                        <?}?>
                        
                        <?if(in_array("SUMM",$arResult["COLUMN_LIST"])){?>
                            <div class="checkout-cart__column cart-item_summa">
                                <span id="cart-item_summa-<?=$item['ID']?>"><?=rtrim(rtrim(number_format($item['FINAL_PRICE'],2,'.',' '),'0'),'.')?></span> ₽
                            </div>
                        <?}?>
                        <div class="cart-item_like _like button-like"
                            data-favorites="<?=$item['PRODUCT']['ID']?>"
                            data-type-id="<?=$item['PRODUCT']['IBLOCK_ID'];?>"
                        >
                            <svg width="18" height="15" viewBox="0 0 18 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M0.600026 5.25841L0.600026 5.25832C0.604534 4.22311 1.03503 3.04339 1.80957 2.12624C2.57701 1.2175 3.6511 0.6 4.93969 0.6C5.51222 0.6 6.2024 0.78456 6.88397 1.08484C7.56081 1.38303 8.18124 1.77417 8.6092 2.13996L8.99919 2.4733L9.38905 2.13981C9.81653 1.77415 10.4369 1.38305 11.1138 1.08485C11.7955 0.784571 12.4858 0.6 13.0584 0.6C14.347 0.6 15.4214 1.21752 16.1892 2.12633C16.9641 3.0435 17.395 4.22322 17.4 5.25841C17.404 6.15275 16.9374 7.17654 16.1228 8.25695C15.3181 9.32421 14.2302 10.3729 13.1211 11.3038C12.0153 12.2321 10.906 13.0287 10.0716 13.594C9.655 13.8764 9.30818 14.1001 9.06628 14.2528C9.04289 14.2676 9.02049 14.2817 8.9991 14.2951C8.97772 14.2817 8.95533 14.2676 8.93196 14.2529C8.69011 14.1001 8.34337 13.8763 7.92682 13.594C7.09264 13.0286 5.98362 12.2318 4.87797 11.3035C3.76917 10.3725 2.68146 9.32382 1.87694 8.2566C1.06251 7.17622 0.595991 6.15253 0.600026 5.25841Z" stroke-width="1.2"/>
                            </svg>
                            <?if(!$arResult['IS_MOBILE']){?>В избранное<?}?>
                        </div>
                        <form action="<?=POST_FORM_ACTION_URI?>" method="POST" name="DELETE_BTN" enctype="multipart/form-data" class="cart-item_delete">
                            <?=bitrix_sessid_post()?>
                            <input type="hidden" name="action" value="delete_basket">
                            <input type="hidden" name="id_basket" value="<?=$item['ID']?>">
                            <button type="submit">
                                <svg width="14" height="17" viewBox="0 0 14 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.5641 1.13333C5.3348 1.13333 5.10925 1.23373 4.93869 1.4222C4.76729 1.6116 4.66667 1.87413 4.66667 2.15333V3.17333H9.33333V2.15333C9.33333 1.87413 9.23272 1.6116 9.06131 1.4222C8.89075 1.23373 8.6652 1.13333 8.4359 1.13333H5.5641ZM10.4103 3.17333V2.15333C10.4103 1.59091 10.2083 1.0463 9.84115 0.640586C9.47314 0.233934 8.96824 0 8.4359 0H5.5641C5.03176 0 4.52686 0.233933 4.15885 0.640586C3.79169 1.0463 3.58974 1.59091 3.58974 2.15333V3.17333H0.538462C0.241077 3.17333 0 3.42704 0 3.74C0 4.05296 0.241077 4.30667 0.538462 4.30667H1.4359V14.8467C1.4359 15.4091 1.63784 15.9537 2.005 16.3594C2.37301 16.7661 2.87791 17 3.41026 17H10.5897C11.1221 17 11.627 16.7661 11.995 16.3594C12.3622 15.9537 12.5641 15.4091 12.5641 14.8467V4.30667H13.4615C13.7589 4.30667 14 4.05296 14 3.74C14 3.42704 13.7589 3.17333 13.4615 3.17333H10.4103ZM2.51282 4.30667V14.8467C2.51282 15.1259 2.61344 15.3884 2.78484 15.5778C2.9554 15.7663 3.18095 15.8667 3.41026 15.8667H10.5897C10.819 15.8667 11.0446 15.7663 11.2152 15.5778C11.3866 15.3884 11.4872 15.1259 11.4872 14.8467V4.30667H2.51282Z"/>
                                </svg>
                                <?if(!$arResult['IS_MOBILE']){?><span>Удалить</span><?}?>
                            </button>
                        </form>
                    </div>
                <?endforeach;?>
            </div>
            <div class="checkout-cart_right">
                <div class="checkout-cart_result">
                    <div class="in_cart">
                        <div class="in_cart-title">В корзине</div>
                        <div class="in_cart-count">
                            <?=count($arResult['BASKET']['ITEMS'])?>
                            <?if(count($arResult['BASKET']['ITEMS']) == 1) echo "товар";
                            elseif(count($arResult['BASKET']['ITEMS']) > 1 && count($arResult['BASKET']['ITEMS']) < 5) echo "товара";
                            elseif(count($arResult['BASKET']['ITEMS']) >= 5) echo "товаров";?>
                        </div>
                        <div class="in_cart-summa_text">Товары на сумму</div>
                        <div class="in_cart-summa_price" id="in_cart-summa-price"><?=rtrim(rtrim(number_format($arResult['BASKET']['FULL_PRICE'],2,'.',' '),'0'),'.')?> ₽</div>
                        <div class="in_cart-discount_text">Выгода на товары</div>
                        <div class="in_cart-discount_price" id="in_cart-discount-price">-<?=rtrim(rtrim(number_format(($arResult['BASKET']['FULL_PRICE'] - $arResult['BASKET']['PRICE']),2,'.',' '),'0'),'.')?> ₽</div>
                    </div>
                    <?if($arResult['IS_MOBILE']):?>
                        <div class="in_pay_fixed">
                            <div class="in_pay-summa">
                                <div class="in_pay-summa_text">Всего к оплате</div>
                                <div class="in_pay-summa_price">
                                    <span id="cart-total-price">
                                        <?=rtrim(rtrim(number_format($arResult['BASKET']['ORDER_PRICE'],2,'.',' '),'0'),'.')?>
                                    </span> ₽
                                </div>
                            </div>
                            <div class="in_pay_fixed-buttons">
                                <?if(!empty($arResult['GIFTS_COLLECTION'])):?>
                                <a href="#link_gifts" class="btn checkout-btn_gift" onclick="
                                $([document.documentElement, document.body]).animate({scrollTop: $('#checkout-cart_gifts').offset().top - 160}, 1000);
                                ">
                                    <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M18.1654 10.5V20.0833H2.83203V10.5M10.5 20.084V5.70898M0.917969 5.70898H20.0846V10.5007H0.917969V5.70898ZM10.5013 5.70768H6.1888C5.55339 5.70768 4.944 5.45526 4.49469 5.00596C4.04539 4.55665 3.79297 3.94726 3.79297 3.31185C3.79297 2.67643 4.04539 2.06704 4.49469 1.61774C4.944 1.16843 5.55339 0.916016 6.1888 0.916016C9.54297 0.916016 10.5013 5.70768 10.5013 5.70768ZM10.5 5.70768H14.8125C15.4479 5.70768 16.0573 5.45526 16.5066 5.00596C16.9559 4.55665 17.2083 3.94726 17.2083 3.31185C17.2083 2.67643 16.9559 2.06704 16.5066 1.61774C16.0573 1.16843 15.4479 0.916016 14.8125 0.916016C11.4583 0.916016 10.5 5.70768 10.5 5.70768Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                                <?endif;?>
                                <a href="<?=$arParams['SEF_FOLDER']?><?=$arParams['SEF_URL_TEMPLATES']['order']?>" class="btn checkout-btn_next_step">Продолжить оформление</a>
                            </div>
                        </div>
                    <?endif;?>
                    <div class=""></div>
                    <div class="in_pay">
                        <div class="in_pay-summa">
                            <div class="in_pay-summa_text">Всего к оплате</div>
                            <div class="in_pay-summa_price">
                                <span id="cart-total-price">
                                    <?=rtrim(rtrim(number_format($arResult['BASKET']['ORDER_PRICE'],2,'.',' '),'0'),'.')?>
                                </span> ₽
                            </div>
                        </div>
                        <a href="<?=$arParams['SEF_FOLDER']?><?=$arParams['SEF_URL_TEMPLATES']['order']?>" class="btn checkout-btn_next_step" onclick="void(0);">Продолжить оформление</a>
                        <?if(!$USER->IsAuthorized()):?>
                            <div class="auth-link">
                                <span onclick="Authentica.modules.Popups.open('auth','<?=SITE_DIR?>local/ajax/auth.php?<?=$params?>','auth');">Авторизируйтесь</span>, чтобы сразу увидеть свой заказ в личном кабинете
                            </div>
                        <?endif;?>
                    </div>
                </div>
                <div class="checout-cart_reserv">
                    Товары зарезервированы на 2 часа
                </div>
                <?if(!empty($arResult['GIFTS_COLLECTION']))
                include('include/cart/gift.php');?>
            </div>
            </div>
            <?}?>
        <?else:?>
            <div class="checkout-empty">
                <?= Loc::getMessage('YOUR_CART_EMPTY') ?><br>
                <?=Loc::getMessage(
			'EMPTY_BASKET_HINT',
			array(
				'#A1#' => '<a href="/">',
				'#A2#' => '</a>'
			))?>
			<?
			if(!$USER->IsAuthorized()) {
				echo Loc::getMessage(
					'EMPTY_BASKET_HINT_AUTH',
					array(
						'#A1#' => '<a style="cursor:pointer;color: #7A7A7A;text-decoration: none;" onclick="authLink();" data-popup-wrapper="auth" data-popup-opener="auth" data-popup-href="/local/ajax/auth.php?backurl=" data-module-inited="Y">',
						'#A2#' => '</a>',
					));
			}?>
            </div>
        <?endif;?>
    </div>

    <?if($arResult['BASKET']['ITEMS']):?>
    <div class="checkout-recommend">
    <?$APPLICATION->IncludeComponent(
	"custom:sale.basket.recommended.products", 
	".default", 
	array(
		"COMPONENT_TEMPLATE" => ".default",
		"IBLOCK_TYPE" => "catalog",
		"IBLOCK_ID" => "2",
		"LIMIT" => "15",
		"AVAILABILITY" => "Y",
		"GROUP_BY" => "BASKET_PRODUCTS",
		"ORDER_BY" => "BASKET_QUANTITY",
		"COUNT_OF_DAYS" => "90",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "86400"
	),
	$component
    ); ?>
    </div>
    <?endif;?>
</section>
<?if($arResult['IS_MOBILE']):?>
<script>
    $(document).ready(function(){
        $(window).scroll(function(){
            var wt = $(window).scrollTop();
	        var wh = $(window).height();
            var et = $('.in_pay').offset().top;
	        var eh = $('.in_pay').outerHeight();
            var dh = $(document).height();
            if (wt + wh >= et || wh + wt == dh || eh + et < wh){
		        $('.in_pay_fixed').removeClass('active');
	        } else {
                $('.in_pay_fixed').addClass('active');
            }
        });
    });
</script>
<?endif;?>