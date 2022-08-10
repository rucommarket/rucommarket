<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(false);
\CJSCore::Init(["jquery","suggestions","masked_input"]);

use \Bitrix\Main,
    \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\Page\Asset;

$this->addExternalJS("https://api-maps.yandex.ru/2.1/?lang=ru_RU");
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
        Оформление заказа
        <svg>
            <path d="M 8.92578 4.359L 17.1068 4.359C 17.1068 1.304 16.2488 1.227 13.0168 1.227C 9.78378 1.227 8.92578 1.305 8.92578 4.359L 8.92578 4.359ZM 0 4.359L 7.628 4.359C 7.628 0.627 9.068 0 13.017 0C 16.966 0 18.405 0.627 18.405 4.359L 26.034 4.359L 26.034 12.181C 26.034 16.482 25.835 20.002 21.283 20.002L 4.75 20.002C 0.199 20.002 0 16.483 0 12.181L 0 4.359Z"></path>
        </svg>
    </h1>
    <div class="checkout-cart" id="checkout-cart">
        <?if($arResult['BASKET']['ITEMS']):?>
            <?include('include/order/basket.php');?>
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
    <h2>Информация о доставке</h2>
    <div class="checkout-delivery">
        <div class="checkout-delivery_profile">
            <h3>Контактные данные</h3>
            <form id="delivery-form_contact" class="checkout-delivery_form" action="<?=$arParams['SEF_FOLDER']?><?=$arParams['SEF_URL_TEMPLATES']['payment']?>" method="POST" name="CONTACT_INPUT" enctype="multipart/form-data">
                <?=bitrix_sessid_post()?>
                <input type="hidden" name="action" value="payment">
                <div class="checkout-delivery_form_row">
                    <input type="tel" id="order_phone" name="PHONE" placeholder="Телефон * (без 8-ки)" required<?
                    if($arResult['CONTACT']['PHONE']) echo ' value="'.$arResult['CONTACT']['PHONE'].'"';
                    ?>>
                    <input type="email" name="EMAIL" placeholder="Эл. почта *" required<?
                    if($arResult['CONTACT']['EMAIL']) echo ' value="'.$arResult['CONTACT']['EMAIL'].'"';
                    ?>>
                </div>
                <div class="checkout-delivery_form_row">
                    <input type="text" name="LAST_NAME" placeholder="Фамилия *" required<?
                    if($arResult['CONTACT']['LAST_NAME']) echo ' value="'.$arResult['CONTACT']['LAST_NAME'].'"';
                    ?>>
                </div>
                <div class="checkout-delivery_form_row">
                    <input type="text" name="NAME" placeholder="Имя *" required<?
                    if($arResult['CONTACT']['NAME']) echo ' value="'.$arResult['CONTACT']['NAME'].'"';
                    ?>>
                </div>
                <div class="checkout-delivery_form_row test-input">
                    <input type="text" name="SECOND_NAME" placeholder="Отчество"<?
                    if($arResult['CONTACT']['SECOND_NAME']) echo ' value="'.$arResult['CONTACT']['SECOND_NAME'].'"';
                    ?>>
                </div>
                <input type="submit" id="contact-form_submit" style="display:none;">
            </form>
        </div>
        <?include('include/order/address.php');?>

        <div class="checkout-shipment">
            <h3>Доставка</h3>
            <?if(isset($arResult['DELIVERIES']) && !empty($arResult['DELIVERIES'])):?>
                <form id="delivery-form_change" action="<?=POST_FORM_ACTION_URI?>" method="POST" name="DELIVERY_INPUT" enctype="multipart/form-data">
                    <?=bitrix_sessid_post()?>
                    <input type="hidden" name="action" value="check_delivery">
                    <div class="delivery-list">
                        <div class="delivery-list_tabs">
                        <?if($arResult['DELIVERIES_MULTI'][0] === true) {?>
                            <div class="delivery-list_tab<?if($arResult['DELIVERIES_MULTI']['CHECKED'] === 0){echo ' active';}?>" data-multi="0">Доставка в руки</div>
                        <?}?>
                        <?if($arResult['DELIVERIES_MULTI'][1] === true) {?>
                            <div class="delivery-list_tab<?if($arResult['DELIVERIES_MULTI']['CHECKED'] === 1){echo ' active';}?>" data-multi="1">Пункт выдачи</div>
                        <?}?>
                        </div>
                        <?foreach($arResult['DELIVERIES'] as $delivery):?>
                            <div class="delivery-item<?
                                if($arResult['DELIVERIES_MULTI']['CHECKED'] === $delivery['DATA']['MULTIVARIANT'])
                                    echo ' active';
                                ?>" data-multi="<?=$delivery['DATA']['MULTIVARIANT'];?>">
                                <input type="radio" name="check_delivery" id="delivery-input-<?=$delivery['ID']?>" value="<?=$delivery['ID']?>" onChange="BX('delivery-form_submit').click();"<?
                                if($delivery['CHECKED'] == 'Y') echo " checked";
                                ?>>
                                <label for="delivery-input-<?=$delivery['ID']?>">
                                    <span class="delivery_title"><?=$delivery['NAME']?></span>
                                    ( <span class="delivery_price"><?=$delivery['PRICE']?></span> руб. )
                                    <span class="delivery_desc"><?=$delivery['DATA']['DELIVERY_TEXT']?></span>
                                </label>
                            </div>
                        <?endforeach;?>
                    </div>
                    <?foreach($arResult['DELIVERIES'] as $delivery):?>
                        <?if($delivery['CHECKED'] == 'Y' && $delivery['DATA']['MULTIVARIANT'] == 1):?>
                            <select class="delivery-multi_select" name="check_delivery_multi">
                                <?foreach($delivery['DATA']['DELIVERY_VARIANTS'] as $variant){?>
                                    <option value="<?=$variant['Id']?>"><?=$variant['Address']?> (<?=$variant['Name']?>) | <?=$variant['Phone']?></option>
                                <?}?>
                            </select>
                            <input type="hidden" name="CHECK_DELIVERY_ID">
                            <input type="hidden" name="CHECK_DELIVERY_NAME">
                        <?endif;?>
                    <?endforeach;?>
                    <div id="delivery-map" style="width: 500px;height: 500px;"></div>
                    <input type="submit" id="delivery-form_submit" style="display:none;">
                </form>
            <?else:?>
                <div class="checkout-shipment_not">
                    Нет доступных способов доставки
                </div>
            <?endif;?>
        </div>

        <div>
        <div class="checkout-payment">
            <h3>Оплата</h3>
            <?if(isset($arResult['PAYMENTS']) && !empty($arResult['PAYMENTS'])):?>
                <form id="payment-form_change" action="<?=POST_FORM_ACTION_URI?>" method="POST" name="PAYMENT_INPUT" enctype="multipart/form-data">
                    <?=bitrix_sessid_post()?>
                    <input type="hidden" name="action" value="check_payment">
                    <div class="payment-list">
                        <?foreach($arResult['PAYMENTS'] as $payment):?>
                            <div class="payment-item">
                                <input type="radio" name="check_payment" id="payment-input-<?=$payment['ID']?>" value="<?=$payment['ID']?>" onChange="BX('payment-form_submit').click();"<?
                                if($payment['CHECKED'] == 'Y') echo " checked";
                                ?>>
                                <label for="payment-input-<?=$payment['ID']?>">
                                    <span class="payment_title"><?=$payment['NAME']?></span>
                                </label>
                            </div>
                        <?endforeach;?>
                    </div>
                    <input type="submit" id="payment-form_submit" style="display:none;">
                </form>
            <?else:?>
                <div class="checkout-payment_not">
                    Нет доступных способов оплаты
                </div>
            <?endif;?>
        </div>
        <?include('include/order/promo.php');?>
        </div>
    </div>

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
        <div class="checkout-cart_delivery">
            <div class="cart-delivery_text">Доставка:</div>
            <div class="cart-delivery_price">
                <span id="cart-delivery-price">
                    <?=$arResult['ORDER']['DELIVERY_PRICE']?>
                </span> руб.
            </div>
        </div>
        <div class="checkout-cart_total">
            <div class="cart-total_text">всего к оплате:</div>
            <div class="cart-total_price">
                <span id="cart-total-price">
                    <?=$arResult['ORDER']['PRICE']?>
                </span> руб.
            </div>
        </div>
        <a onclick="BX('contact-form_submit').click();" class="btn checkout-btn_next_step">Перейти к оплате</a>
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

<?if($arResult['BASKET']['ITEMS']):?>
<script>
    BX.ready(function(){
        new BX.MaskedInput({
            mask: '+7 (999) 999-9999',
            input: BX('order_phone'),
            placeholder: '_'
        });
    });

    $(document).ready(function(){
        var url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address";
        var token = "8be990c9b1616371ecf429ad001f697820d914c1";
        $("#address_string").suggestions({
        token: token,
        type: "ADDRESS",
        partner: "authentica_address",
        onSelect: function(suggestion) {
            var address = suggestion.data;
            $('[name="address_index"]').val(address.postal_code);
            $('[name="address_region"]').val(address.region);
            if (address.geo_lat && address.geo_lon) {
                $('[name="address_lat"]').val(address.geo_lat);
                $('[name="address_lon"]').val(address.geo_lon);
            } else {
                $('[name="address_lat"]').val('');
                $('[name="address_lon"]').val('');
            }
            if (address.kladr_id) {
                $('[name="address_kladr"]').val(address.kladr_id);
            } else {
                $('[name="address_kladr"]').val('');
            }
            if (address.city) {
                $('[name="address_city"]').val(address.city);
            } else if(address.settlement) {
                $('[name="address_city"]').val(address.settlement);
            }
                $('[name="address_street"]').val(address.street);
            if(address.house_type && address.house) {
                $('[name="address_house').val(address.house);
            } else {
                $('[name="address_house').val('');
            }
            if(address.block) {
                $('[name="address_building"]').val(address.block);
            } else {
                $('[name="address_building"]').val('');
            }
            if(address.flat_type && address.flat) {
                $('[name="address_flat').val(address.flat);
            } else {
                $('[name="address_flat').val('');
            }
            if (!address.city && !address.settlement) {
              showMessage("Укажите населённый пункт");
            } else if (!address.settlement && !address.street) {
              showMessage("Укажите улицу");
            } else if (!address.house) {
              showMessage("Укажите дом");
            } else {
              $("#error_code_address").text('');
              BX('address-form_submit').click();
            }
        }
        });
        function showMessage(message) {
            $("#error_code_address").text(message);
        }
    });

    ymaps.ready(function () {
        var myMap = new ymaps.Map('delivery-map', {
                center: [
                    <?=($arResult['ADDRESS']['LAT'])?:'55.751574'?>,
                    <?=($arResult['ADDRESS']['LON'])?:'37.573856'?>
                ],
                zoom: 12,
                controls: ['geolocationControl', 'zoomControl']
            }, {
                geolocationControlFloat: 'right',
                zoomControlSize: 'large'
            }),
        
            placemarkHouse = new ymaps.Placemark([
                <?=($arResult['ADDRESS']['LAT'])?:'55.751574'?>,
                <?=($arResult['ADDRESS']['LON'])?:'37.573856'?>
            ], {
                hintContent: 'Адрес доставки'
            }, {
                preset: 'islands#redCircleDotIcon'
            });
        
        myMap.geoObjects
            .add(placemarkHouse);
    });
</script>
<?endif;?>