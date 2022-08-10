<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(false);

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
?>

<?if(is_array($arResult["COLUMN_LIST"]) && !empty($arResult["COLUMN_LIST"])):?>
<style>
    section.checkout .checkout-cart .checkout-cart_container .checkout-cart_row {
        grid-template-columns: 45% repeat(<?=(count($arResult["COLUMN_LIST"]) - 1)?>,1fr);
    }
</style>
<?endif;?>

<section class="checkout">
    <h1 class="checkout-header">
        <?= Loc::getMessage('YOUR_CART') ?>
        <svg>
            <path d="M 8.92578 4.359L 17.1068 4.359C 17.1068 1.304 16.2488 1.227 13.0168 1.227C 9.78378 1.227 8.92578 1.305 8.92578 4.359L 8.92578 4.359ZM 0 4.359L 7.628 4.359C 7.628 0.627 9.068 0 13.017 0C 16.966 0 18.405 0.627 18.405 4.359L 26.034 4.359L 26.034 12.181C 26.034 16.482 25.835 20.002 21.283 20.002L 4.75 20.002C 0.199 20.002 0 16.483 0 12.181L 0 4.359Z"></path>
        </svg>
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
                                            <?=$item['PROPERTIES'][$property]['NAME'];?>:&nbsp;
                                            <?=$item['PROPERTIES'][$property]['VALUE'];?>
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
                                    <?=$item['PRICE']?> ₽
                                    <?if($item['BASE_PRICE'] != $item['PRICE']):?>
                                        <span><?=$item['BASE_PRICE']?> ₽</span>
                                    <?endif;?>
                                <?} else {?>
                                    <span>ПОДАРОК</span>
                                <?}?>
                            </div>
                        <?}?>
                        <?if(in_array("QUANTITY",$arResult["COLUMN_LIST"])){?>
                            <div class="checkout-cart__column cart-item_quantity">
                                <?if(!$item['PROPERTIES']['IS_GIFT'] || $item['PROPERTIES']['IS_GIFT']['VALUE'] != 'Y'){?>
                                    <span class="cart-quantity_btn cart-quantity_before" data-event="Down" data-id="<?=$item['ID']?>">-</span>
                                    <input type="number" min="1" max="100" class="cart-quantity" data-id="<?=$item['ID']?>" value="<?=$item['QUANTITY']?>">
                                    <span class="cart-quantity_btn cart-quantity_after"  data-event="Up" data-id="<?=$item['ID']?>">+</span>
                                <?} else {?>
                                    <input type="number" min="1" max="100" class="cart-quantity" data-id="<?=$item['ID']?>" value="<?=$item['QUANTITY']?>" readonly>
                                <?}?>
                            </div>
                        <?}?>
                        
                        <?if(in_array("SUMM",$arResult["COLUMN_LIST"])){?>
                            <div class="checkout-cart__column cart-item_summa">
                                <span id="cart-item_summa-<?=$item['ID']?>"><?=$item['FINAL_PRICE']?></span> ₽
                            </div>
                        <?}?>
                        <?if(!$item['PROPERTIES']['IS_GIFT'] || $item['PROPERTIES']['IS_GIFT']['VALUE'] != 'Y'){?>
                        <form action="<?=POST_FORM_ACTION_URI?>" method="POST" name="DELETE_BTN" enctype="multipart/form-data" class="cart-item_delete">
                            <?=bitrix_sessid_post()?>
                            <input type="hidden" name="action" value="delete_basket">
                            <input type="hidden" name="id_basket" value="<?=$item['ID']?>">
                            <button type="submit">
                                <svg viewBox="0 0 17 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.75641 1.33333C6.47797 1.33333 6.20408 1.45145 5.99698 1.67317C5.78885 1.896 5.66667 2.20486 5.66667 2.53333V3.73333H11.3333V2.53333C11.3333 2.20486 11.2112 1.896 11.003 1.67317C10.7959 1.45145 10.522 1.33333 10.2436 1.33333H6.75641ZM12.641 3.73333V2.53333C12.641 1.87166 12.3958 1.23094 11.95 0.75363C11.5031 0.275216 10.89 0 10.2436 0H6.75641C6.10999 0 5.4969 0.275216 5.05003 0.75363C4.60419 1.23094 4.35897 1.87166 4.35897 2.53333V3.73333H0.653846C0.292737 3.73333 0 4.03181 0 4.4C0 4.76819 0.292737 5.06667 0.653846 5.06667H1.74359V17.4667C1.74359 18.1283 1.98881 18.7691 2.43465 19.2464C2.88152 19.7248 3.49461 20 4.14103 20H12.859C13.5054 20 14.1185 19.7248 14.5654 19.2464C15.0112 18.7691 15.2564 18.1283 15.2564 17.4667V5.06667H16.3462C16.7073 5.06667 17 4.76819 17 4.4C17 4.03181 16.7073 3.73333 16.3462 3.73333H12.641ZM3.05128 5.06667V17.4667C3.05128 17.7951 3.17346 18.104 3.3816 18.3268C3.5887 18.5485 3.86259 18.6667 4.14103 18.6667H12.859C13.1374 18.6667 13.4113 18.5486 13.6184 18.3268C13.8265 18.104 13.9487 17.7951 13.9487 17.4667V5.06667H3.05128Z"/>
                                </svg>
                                <span>Удалить</span>
                            </button>
                        </form>
                        <?}?>
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
                            if(count($arResult['BASKET']['ITEMS']) > 1 && count($arResult['BASKET']['ITEMS']) < 5) echo "товара";
                            if(count($arResult['BASKET']['ITEMS']) >= 5) echo "товаров";?>
                        </div>
                        <div class="in_cart-summa_text">Товары на сумму</div>
                        <div class="in_cart-summa_price"><?=$arResult['BASKET']['PRICE']?> ₽</div>
                        <div class="in_cart-discount_text">Выгода на товары</div>
                        <div class="in_cart-discount_price">-<?=($arResult['BASKET']['FULL_PRICE'] - $arResult['BASKET']['PRICE'])?> ₽</div>
                    </div>
                    <div class="in_pay">
                        <div class="in_pay-summa">
                            <div class="in_pay-summa_text">Всего к оплате</div>
                            <div class="in_pay-summa_price">
                                <span id="cart-total-price">
                                    <?=$arResult['BASKET']['PRICE']?>
                                </span> ₽
                            </div>
                        </div>
                        <a href="<?=$arParams['SEF_FOLDER']?><?=$arParams['SEF_URL_TEMPLATES']['order']?>" class="btn checkout-btn_next_step">Продолжить оформление</a>
                    </div>
                </div>
                <div class="checout-cart_reserv">
                    Товары зарезервированы на 2 часа
                </div>
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
			global $USER;
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
    <div class="checkout-gift">
        <?$APPLICATION->IncludeComponent(
	        "custom:sale.gift.basket", 
	        ".default", 
	        array(
	        	"SHOW_PRICE_COUNT" => "1",
	        	"PRODUCT_SUBSCRIPTION" => "N",
	        	"PRODUCT_ID_VARIABLE" => "id",
	        	"PARTIAL_PRODUCT_PROPERTIES" => "N",
	        	"USE_PRODUCT_QUANTITY" => "N",
	        	"ACTION_VARIABLE" => "actionGift",
	        	"ADD_PROPERTIES_TO_BASKET" => "Y",
	        	"BASKET_URL" => $APPLICATION->GetCurPage(),
	        	"APPLIED_DISCOUNT_LIST" => $arResult["APPLIED_DISCOUNT_LIST"],
	        	"FULL_DISCOUNT_LIST" => $arResult["FULL_DISCOUNT_LIST"],
	        	"TEMPLATE_THEME" => '.default',
	        	"PRICE_VAT_INCLUDE" => "Y",
	        	"CACHE_GROUPS" => "N",
	        	"BLOCK_TITLE" => 'Выберите подарок',
	        	"HIDE_BLOCK_TITLE" => "N",
	        	"TEXT_LABEL_GIFT" => 'Подарок',
	        	"PRODUCT_QUANTITY_VARIABLE" => 'quantity',
	        	"PRODUCT_PROPS_VARIABLE" => 'prop',
	        	"SHOW_OLD_PRICE" => "Y",
	        	"SHOW_DISCOUNT_PERCENT" => "N",
	        	"SHOW_NAME" => "Y",
	        	"SHOW_IMAGE" => "Y",
	        	"MESS_BTN_BUY" => 'Выбрать',
	        	"MESS_BTN_DETAIL" => 'Подробнее',
	        	"PAGE_ELEMENT_COUNT" => 40,
	        	"CONVERT_CURRENCY" => "N",
	        	"HIDE_NOT_AVAILABLE" => "Y",
	        	"LINE_ELEMENT_COUNT" => 40,
	        	"COMPONENT_TEMPLATE" => ".default",
	        	"IBLOCK_TYPE" => "catalog",
	        	"IBLOCK_ID" => "2",
	        	"SHOW_FROM_SECTION" => "N",
	        	"SECTION_ID" => $GLOBALS["CATALOG_CURRENT_SECTION_ID"],
	        	"SECTION_CODE" => "",
	        	"SECTION_ELEMENT_ID" => $GLOBALS["CATALOG_CURRENT_ELEMENT_ID"],
	        	"SECTION_ELEMENT_CODE" => "",
	        	"DEPTH" => "2",
	        	"MESS_BTN_SUBSCRIBE" => "Подписаться",
	        	"DETAIL_URL" => "",
	        	"CACHE_TYPE" => "A",
	        	"CACHE_TIME" => "36000000",
	        	"PRICE_CODE" => array(
	        		0 => "BASE",
	        	),
	        	"SHOW_PRODUCTS_2" => "Y",
	        	"PROPERTY_CODE_2" => array(
	        		0 => "VOLUME",
	        		1 => "BRAND_REF",
	        		2 => "",
	        	),
	        	"CART_PROPERTIES_2" => array(
	        		0 => "",
	        		1 => "",
	        	),
	        	"ADDITIONAL_PICT_PROP_2" => "MORE_PHOTO",
	        	"SHOW_PRODUCTS_28" => "Y",
	        	"PROPERTY_CODE_28" => array(
	        		0 => "VOLUME",
	        		1 => "BRAND_REF",
	        		2 => "",
	        	),
	        	"CART_PROPERTIES_28" => array(
	        		0 => "",
	        		1 => "",
	        	),
	        	"ADDITIONAL_PICT_PROP_28" => "MORE_PHOTO",
	        	"SHOW_PRODUCTS_32" => "N",
	        	"PROPERTY_CODE_32" => array(
	        		0 => "VOLUME",
	        	),
	        	"CART_PROPERTIES_32" => array(
	        	),
	        	"ADDITIONAL_PICT_PROP_32" => "MORE_PHOTO",
	        	"SHOW_PRODUCTS_36" => "N",
	        	"PROPERTY_CODE_36" => array(
	        	),
	        	"CART_PROPERTIES_36" => array(
	        	),
	        	"ADDITIONAL_PICT_PROP_36" => "",
	        	"HIDE_PRODUCTS_IN_BASKET" => "N"
	        ),
	        false
        );?>
    </div>
    <div class="checkout-footer">
        <div class="checkout-cart_total">
            <div class="cart-total_text">всего к оплате:</div>
            <div class="cart-total_price">
                <span id="cart-total-price">
                    <?=$arResult['BASKET']['PRICE']?>
                </span> руб.
            </div>
        </div>
        <a href="<?=$arParams['SEF_FOLDER']?><?=$arParams['SEF_URL_TEMPLATES']['order']?>" class="btn checkout-btn_next_step">Продолжить оформление</a>
        <div class="checkout-footer_description">
            оплата возможна<br>
            только по безналичному расчету
        </div>
        <div class="checkout-footer_icons">
            <img src="/local/templates/authentica/img/order_ps_icons.png.webp" alt="payment-icons">
        </div>
    </div>
    <?endif;?>
</section>