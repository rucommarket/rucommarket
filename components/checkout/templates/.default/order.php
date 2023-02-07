<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddChainItem('Оформление заказа',$arParams['SEF_FOLDER'].$arParams['SEF_URL_TEMPLATES']['order']);

$this->setFrameMode(false);
\CJSCore::Init(["jquery","suggestions","masked_input"]);

use \Bitrix\Main,
    \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\Page\Asset;

global $USER;

$this->addExternalJS("https://api-maps.yandex.ru/2.1/?lang=ru_RU");
$this->addExternalCss("/local/templates/authentica/css/dolyame_description.css");
?>
<style>
    .header {
        display: none;
    }
    .footer {
        display: none;
    }
    <?if($arResult['IS_MOBILE']):?>
    .skin{
        padding-left: 0;
        padding-right: 0;
    }
    <?endif;?>
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

<section class="checkout-order<?if($arResult['IS_MOBILE']) echo ' mobile';?>">
    <div class="checkout-order_registration">
        <a href="/">
            <img src="<?=SITE_TEMPLATE_PATH?>/img/logo.png" alt="Профессиональная косметика на сайте Authentica" title="Интернет-магазин профессиональной косметики Authentica.love" />
        </a>
    <?$APPLICATION->IncludeComponent("bitrix:breadcrumb", ".default", Array(
                    "PATH" => "",	// Путь, для которого будет построена навигационная цепочка (по умолчанию, текущий путь)
                    "SITE_ID" => "s1",	// Cайт (устанавливается в случае многосайтовой версии, когда DOCUMENT_ROOT у сайтов разный)
                    "START_FROM" => "0",	// Номер пункта, начиная с которого будет построена навигационная цепочка
                ),
                $component
            );?>
    <h1 class="checkout-header">
        Оформление заказа
    </h1>
    <?if($arResult['BASKET']['ITEMS']):?>
    <?/*$isGroup45 = false;
        if($USER->IsAuthorized() && in_array(45, $USER->GetUserGroup($USER->GetID()))) {
            $userEmail=$USER->GetEmail();
            $isGroup45 = true;
    }*/?>
    <h2>Контактные данные</h2>
        <div class="checkout-delivery_profile">
            <form id="delivery-form_contact" class="checkout-delivery_form" action="<?=$arParams['SEF_FOLDER']?><?=$arParams['SEF_URL_TEMPLATES']['order']?>" method="POST" name="CONTACT_INPUT" enctype="multipart/form-data">
                <?=bitrix_sessid_post()?>
                <input type="hidden" name="action" value="payment">
                <div class="checkout-delivery_form_row">
                    <input type="text" id="order_phone" name="PHONE" placeholder="Телефон* (без 8-ки)" required<?
                        if($arResult['CONTACT']['PHONE']) echo ' value="'.$arResult['CONTACT']['PHONE'].'"';
                        ?>
                        <?if(isset($arResult['ERRORS']['CONTACT']['PHONE']) && !empty($arResult['ERRORS']['CONTACT']['PHONE']))
                        echo ' class="error"';
                        ?>
                    >
                    <input type="text" name="EMAIL" placeholder="Эл. почта*" required<?
                        if($arResult['CONTACT']['EMAIL']) echo ' value="'.$arResult['CONTACT']['EMAIL'].'"';
                        ?>
                        <?if(isset($arResult['ERRORS']['CONTACT']['EMAIL']) && !empty($arResult['ERRORS']['CONTACT']['EMAIL']))
                        echo ' class="error"';
                        ?>
                    >
                </div>
                <div class="checkout-delivery_form_row">
                    <input type="text" name="LAST_NAME" placeholder="Фамилия*" required<?
                        if($arResult['CONTACT']['LAST_NAME']) echo ' value="'.$arResult['CONTACT']['LAST_NAME'].'"';
                        ?>
                        <?if(isset($arResult['ERRORS']['CONTACT']['LAST_NAME']) && !empty($arResult['ERRORS']['CONTACT']['LAST_NAME']))
                        echo ' class="error"';
                        ?>
                    >
                    <input type="text" name="NAME" placeholder="Имя*" required<?
                        if($arResult['CONTACT']['NAME']) echo ' value="'.$arResult['CONTACT']['NAME'].'"';
                        ?>
                        <?if(isset($arResult['ERRORS']['CONTACT']['NAME']) && !empty($arResult['ERRORS']['CONTACT']['NAME']))
                        echo ' class="error"';
                        ?>
                    >
                </div>
                <div class="checkout-delivery_form_row">
                    <input type="text" name="SECOND_NAME" placeholder="Отчество"<?
                    if($arResult['CONTACT']['SECOND_NAME']) echo ' value="'.$arResult['CONTACT']['SECOND_NAME'].'"';
                    ?>>
                </div>
                <input type="submit" id="contact-form_submit" style="display:none;">
            </form>
            <?if(isset($arResult['ERRORS']['CONTACT']) && !empty($arResult['ERRORS']['CONTACT'])):?>
            <div id="error_contact">Не заполнены обязательные поля</div>
            <?endif?>
        </div>
        <h2>Адрес доставки</h2>
        <?include('include/order/address.php');?>

        <h2>
            Способ доставки
            <span style="display:block;font-size:12px;color:#CECECE;text-transform: none;">
                * Заказы передаются в службу доставки только в рабочие дни
            </span>
        </h2>
        <?include('include/order/delivery.php');?>

        <h2>Способы оплаты</h2>
        <div class="checkout-payment">
            <?if(isset($arResult['PAYMENTS']) && !empty($arResult['PAYMENTS'])):?>
                <form id="payment-form_change" action="<?=POST_FORM_ACTION_URI?>" method="POST" name="PAYMENT_INPUT" enctype="multipart/form-data">
                    <?=bitrix_sessid_post()?>
                    <input type="hidden" name="action" value="check_payment">
                    <div class="payment-list">
                        <?foreach($arResult['PAYMENTS'] as $payment):?>
                            <div class="payment-item<?
                                if($payment['CHECKED'] == 'Y') echo " active";
                                ?>">
                                <input type="radio" name="check_payment" id="payment-input-<?=$payment['ID']?>" value="<?=$payment['ID']?>" onChange="BX('payment-form_submit').click();"<?
                                if($payment['CHECKED'] == 'Y') echo " checked";
                                ?>>
                                <label for="payment-input-<?=$payment['ID']?>">
                                    <?if($payment['LOGOTIP']){?>
                                        <img src="<?=CFile::GetPath($payment['LOGOTIP']);?>" alt="<?=$payment['NAME']?>">
                                    <?}?>
                                    <span class="payment_title"><?=$payment['NAME']?></span>
                                </label>
                            </div>
                        <?endforeach;?>
                    </div>
                    <?foreach($arResult['PAYMENTS'] as $payment):?>
                        <?if($payment['CHECKED'] == 'Y' && !empty($payment['DESCRIPTION'])):?>
                            <div class="payment-description">
                                <?=$payment['DESCRIPTION'];?>
                            </div>
                        <?endif;?>
                    <?endforeach;?>
                    <input type="submit" id="payment-form_submit" style="display:none;">
                </form>
            <?else:?>
                <div class="checkout-payment_not">
                    Нет доступных способов оплаты
                </div>
            <?endif;?>
        </div>
        <?if(!$arResult['IS_MOBILE']):?>
        <a href="<?=$arParams['SEF_FOLDER']?>" class="checkout-btn_prev_step" onclick="void(0);">
            <svg width="8" height="11" viewBox="0 0 8 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M2.46195 5.5L7.99832 0.690857L7.33003 0L0.998322 5.5L7.33003 11L7.99832 10.3091L2.46195 5.5Z" fill="#FE4A5B"/>
            </svg>Вернуться в корзину
        </a>
        <?endif;?>
    
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
    <div class="checkout-order_right">
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
            <?include('include/cart/gift.php');?>
            <?include('include/order/promo.php');?>
            <?include('include/order/digift.php');?>
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
            <div class="checkout-order_next">
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

                <a onclick="BX('contact-form_submit').click();" class="btn checkout-btn_next_step">Перейти к оплате</a>
                <?if(!$USER->IsAuthorized()):?>
                    <div class="auth-link">
                        <span onclick="Authentica.modules.Popups.open('auth','<?=SITE_DIR?>local/ajax/auth.php?<?=$params?>','auth');">Авторизируйтесь</span>, чтобы сразу увидеть свой заказ в личном кабинете
                    </div>
                <?endif;?>
            </div>
            <?if($arResult['IS_MOBILE']):?>
            <a href="<?=$arParams['SEF_FOLDER']?>" class="checkout-btn_prev_step" onclick="void(0);">
                <svg width="8" height="11" viewBox="0 0 8 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M2.46195 5.5L7.99832 0.690857L7.33003 0L0.998322 5.5L7.33003 11L7.99832 10.3091L2.46195 5.5Z" fill="#FE4A5B"/>
                </svg>Вернуться в корзину
            </a>
            <?endif;?>
        </div>
    </div>
    <?endif;?>
</section>

<?if($arResult['BASKET']['ITEMS']):?>
<script>
    BX.ready(function(){
        /*new BX.MaskedInput({
            mask: '+7 (999) 999-9999',
            input: BX('order_phone'),
            placeholder: '_'
        });*/
        $("#order_phone").inputmask("+7 (999) 999-9999");
        BX.bind(
        BX('delivery-form_contact'), 'focusout',
            function(e){
                let formData = BX.ajax.prepareForm(BX("delivery-form_contact")).data;
                BX.ajax.runComponentAction('rucommarket:checkout','editFormContact', {
                    mode: 'ajax',
                    data: {
                        'form': formData,
                    }
                });
            }
        );
        BX.bindDelegate(
            BX('address-form_change'), 'focusout', {tagName: 'textarea'},
                BX.proxy(function(e){
                    value = e.target.value;
                    BX.ajax.runComponentAction('rucommarket:checkout','editFormAddressComment', {
                        mode: 'ajax',
                        data: {
                            'comment': value,
                        }
                    });
                })
        );
        $.each($( '.error'), function(i, e){
            $([document.documentElement, document.body]).animate({
                scrollTop: $(e).offset().top - 100
            }, 1000);
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
            if(address.street) {
                $('[name="address_street"]').val(address.street);
            } else {
                $('[name="address_street"]').val('');
            }
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
            } else if (!address.settlement && !address.street && address.kladr_id.length < 19) {
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
        $('#delivery-form_contact').on('input', 'input[name="NAME"]', function(){
	        this.value = this.value.replace(/[^A-Za-zА-Яа-яЁё]/g, '');
        });
        $('#delivery-form_contact').on('input', 'input[name="LAST_NAME"]', function(){
	        this.value = this.value.replace(/[^A-Za-zА-Яа-яЁё\-]/g, '');
        });
        $('#delivery-form_contact').on('input', 'input[name="SECOND_NAME"]', function(){
	        this.value = this.value.replace(/[^A-Za-zА-Яа-яЁё]/g, '');
        });
        $('#delivery-form_contact').on('input', 'input[name="EMAIL"]', function(){
	        this.value = this.value.replace(/[\s]/g, '');
        });
    });
</script>
<?if(isset($arResult['ERRORS']['ORDER']) && !empty($arResult['ERRORS']['ORDER'])):?>
    <div id="error_order">
        <div class="error_order-text">
            <?=$arResult['ERRORS']['ORDER']?>
        </div>
        <a href="<?=$arParams['SEF_FOLDER']?><?=$arParams['SEF_URL_TEMPLATES']['order']?>" class="btn error_order-btn">Обновить состав корзины</a>
    </div>
<?endif?>
<?endif;?>