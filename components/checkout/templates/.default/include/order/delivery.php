
<div class="checkout-shipment">
    <?if(isset($arResult['DELIVERIES']) && !empty($arResult['DELIVERIES'])):?>
        <span id="error_code_delivery"><?if(isset($arResult['ERRORS']['DELIVERY']) && !empty($arResult['ERRORS']['DELIVERY'])) echo $arResult['ERRORS']['DELIVERY'];?></span>
        <form id="delivery-form_change" action="<?=POST_FORM_ACTION_URI?>" method="POST" name="DELIVERY_INPUT" enctype="multipart/form-data"
        <?if(isset($arResult['ERRORS']['DELIVERY']) && !empty($arResult['ERRORS']['DELIVERY'])) {?>
            class="error"
        <?}?>
        >
            <?=bitrix_sessid_post()?>
            <input type="hidden" name="action" value="check_delivery">
            <div class="delivery-list">
                <div class="delivery-list_tabs">
                <?if($arResult['DELIVERIES_MULTI'][0] === true) {?>
                    <div class="delivery-list_tab<?if($arResult['DELIVERIES_MULTI']['CHECKED'] <= 0){echo ' active';}?>" data-multi="0">
                        <svg width="22" height="16" viewBox="0 0 22 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M0.956522 0.896552V12.1034H12.4348V0.896552H0.956522ZM13.3913 4.03448H18.0947C18.273 4.03448 18.4333 4.10036 18.5518 4.20084C18.6356 4.27186 18.756 4.36331 18.9122 4.482C19.0258 4.56835 19.1585 4.6691 19.3098 4.78689C19.6526 5.05384 20.0589 5.38436 20.4484 5.75738C20.8365 6.12908 21.2186 6.55312 21.5058 7.00953C21.7921 7.46447 22 7.97686 22 8.51724V12.3729C22 12.7211 21.6985 13 21.3304 13H0.669565C0.299775 13 0 12.719 0 12.3724V0.627586C0 0.28098 0.299774 0 0.669565 0H12.7217C13.0915 0 13.3913 0.28098 13.3913 0.627586V4.03448ZM13.3913 4.93103V12.1034H21.0435V8.51724C21.0435 8.19169 20.9168 7.83815 20.6819 7.46502C20.4481 7.09337 20.1226 6.72721 19.7648 6.38444C19.4083 6.04299 19.0302 5.73477 18.6986 5.47657C18.5773 5.38214 18.4566 5.2903 18.3449 5.20522C18.2082 5.1012 18.0848 5.00728 17.9897 4.93103H13.3913Z" fill="black"/>
                            <circle cx="5.18024" cy="13.1314" r="2.36774" fill="white" stroke="black"/>
                            <path d="M19.5831 13.1298C19.5831 14.4362 18.5233 15.4959 17.2154 15.4959C15.9075 15.4959 14.8477 14.4362 14.8477 13.1298C14.8477 11.8233 15.9075 10.7637 17.2154 10.7637C18.5233 10.7637 19.5831 11.8233 19.5831 13.1298Z" fill="white" stroke="black"/>
                        </svg>
                        <div>
                            Курьером
                            <span>
                                <?if(is_numeric($arResult['DELIVERIES_MULTI']['PRICE'][0])){?>
                                    от <?=rtrim(rtrim(number_format(($arResult['DELIVERIES_MULTI']['PRICE'][0]),2,'.',' '),'0'),'.')?> ₽
                                <?}?>
                            </span>
                        </div>
                    </div>
                <?}?>
                <?if($arResult['DELIVERIES_MULTI'][1] === true) {?>
                    <div class="delivery-list_tab<?if($arResult['DELIVERIES_MULTI']['CHECKED'] === 1){echo ' active';}?>" data-multi="1">
                        <svg width="20" height="22" viewBox="0 0 20 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.68603 0.348755C9.08572 0.120226 9.53886 0 10 0C10.4611 0 10.9142 0.120215 11.3139 0.348724C11.3143 0.348934 11.3147 0.349143 11.315 0.349353L18.6834 4.52353C19.0207 4.71656 19.3099 4.98104 19.5309 5.29724C19.5756 5.33512 19.6147 5.38115 19.6457 5.43468C19.6727 5.48131 19.6916 5.53034 19.703 5.58021C19.8974 5.95095 19.9996 6.3632 20 6.7824V15.1313C19.9995 15.5895 19.8775 16.04 19.6463 16.4365C19.4151 16.833 19.083 17.162 18.6834 17.3907L18.6814 17.3919L11.315 21.5649C11.3146 21.5651 11.3142 21.5654 11.3137 21.5656C11.0067 21.7411 10.6682 21.8527 10.3187 21.895C10.2307 21.9609 10.1212 22 10.0025 22C9.88434 22 9.77524 21.9612 9.68739 21.8957C9.33573 21.854 8.99502 21.7421 8.68621 21.5656C8.6858 21.5653 8.68539 21.5651 8.68498 21.5649L1.31859 17.3919L1.31656 17.3907C0.916991 17.162 0.58486 16.833 0.353685 16.4365C0.122501 16.04 0.00047439 15.5901 2.82338e-07 15.1318L0 6.78294C0.000434141 6.36331 0.102795 5.9501 0.297563 5.57907C0.308982 5.5296 0.327878 5.48095 0.354668 5.43468C0.385471 5.38148 0.424234 5.33569 0.468599 5.29794C0.689673 4.98143 0.97906 4.7167 1.31656 4.52353L1.31859 4.52237L8.68498 0.349353C8.68533 0.349154 8.68568 0.348954 8.68603 0.348755ZM1.08728 6.45936C1.06446 6.56541 1.05276 6.67394 1.05263 6.78322V15.131C1.05296 15.4047 1.12584 15.6735 1.26413 15.9107C1.40231 16.1477 1.60101 16.3448 1.84053 16.4821C1.8408 16.4822 1.84026 16.4819 1.84053 16.4821L9.20976 20.6567C9.29492 20.7055 9.38414 20.746 9.47623 20.7781V11.2702L1.08728 6.45936ZM10.5289 11.2675V20.7763C10.6191 20.7445 10.7067 20.7046 10.7902 20.6567L10.7923 20.6556L18.1587 16.4826C18.1589 16.4824 18.1584 16.4827 18.1587 16.4826C18.3982 16.3453 18.5977 16.1477 18.7359 15.9107C18.8742 15.6735 18.9471 15.4045 18.9474 15.1308V6.78348C18.9473 6.67416 18.9356 6.56561 18.9128 6.45953L10.5289 11.2675ZM18.3592 5.56788C18.2965 5.51786 18.2298 5.47247 18.1596 5.43223C18.1593 5.43204 18.159 5.43186 18.1587 5.43168L10.7923 1.25866L10.7902 1.2575C10.5501 1.12006 10.2776 1.04762 10 1.04762C9.72244 1.04762 9.44989 1.12006 9.20976 1.2575L9.20773 1.25866L1.84134 5.43168C1.84102 5.43186 1.8407 5.43204 1.84038 5.43223C1.77023 5.47244 1.70358 5.51779 1.64093 5.56777L10.0002 10.3616L18.3592 5.56788Z" fill="black"/>
                        </svg>
                        <div>
                            Самовывоз
                            <span>
                                <?if(is_numeric($arResult['DELIVERIES_MULTI']['PRICE'][1])){?>
                                    от <?=rtrim(rtrim(number_format(($arResult['DELIVERIES_MULTI']['PRICE'][1]),2,'.',' '),'0'),'.')?> ₽
                                <?}?>
                            </span>
                        </div>
                    </div>
                <?}?>
                </div>
                <div class="delivery-items">
                    <?$deliveryTabActive = false;?>
                    <?if($arResult['DELIVERIES_MULTI'][0] === true):?>
                        <div class="delivery-item<?
                            if($arResult['DELIVERIES_MULTI']['CHECKED'] <= 0) {
                                echo ' active';
                                $deliveryTabActive = true;
                            }
                        ?>" data-multi="0">
                            <?foreach($arResult['DELIVERIES'] as $delivery):?>
                                <?if($delivery['DATA']['MULTIVARIANT'] == 0):?>
                                <div class="delivery-item_input<?
                                    if($delivery['CHECKED'] == 'Y') echo ' active';
                                ?>">
                                <input type="radio" name="check_delivery" id="delivery-input-<?=$delivery['ID']?>" value="<?=$delivery['ID']?>" onChange="BX('delivery-form_submit').click();"<?
                                    if($delivery['CHECKED'] == 'Y') echo " checked";
                                ?>>
                                <label for="delivery-input-<?=$delivery['ID']?>">
                                    <span class="delivery_title"><?=$delivery['NAME']?></span>
                                    <span class="delivery_price"><?=rtrim(rtrim(number_format(($delivery['PRICE']),2,'.',' '),'0'),'.')?> ₽</span>
                                    <span class="delivery_text"><?=$delivery['DATA']['DELIVERY_TEXT']?></span>
                                </label>
                                </div>
                                <?endif;?>
                            <?endforeach;?>
                        </div>
                    <?endif;?>
                    <?if($arResult['DELIVERIES_MULTI'][1] === true):?>
                        <div class="delivery-item<?
                            if($arResult['DELIVERIES_MULTI']['CHECKED'] == 1) {
                                echo ' active';
                                $deliveryTabActive = true;
                            }
                        ?>" data-multi="1">
                            <?$checkedMulti = false;?>
                            <?foreach($arResult['DELIVERIES'] as $delivery):?>
                                <?if($delivery['DATA']['MULTIVARIANT'] == 1):?>
                                <div class="delivery-item_input<?
                                    if($delivery['CHECKED'] == 'Y') {
                                        echo ' active';
                                        $checkedMulti = true;
                                    }
                                ?>">
                                <input type="radio" name="check_delivery" id="delivery-input-<?=$delivery['ID']?>" value="<?=$delivery['ID']?>" onChange="BX('delivery-form_submit').click();"<?
                                    if($delivery['CHECKED'] == 'Y') echo " checked";
                                ?>>
                                <label for="delivery-input-<?=$delivery['ID']?>">
                                    <span class="delivery_title"><?=$delivery['NAME']?></span>
                                    <span class="delivery_price"><?=rtrim(rtrim(number_format(($delivery['PRICE']),2,'.',' '),'0'),'.')?> ₽</span>
                                    <span class="delivery_text"><?=$delivery['DATA']['DELIVERY_TEXT']?></span>
                                </label>
                                </div>
                                <?endif;?>
                            <?endforeach;?>
                            <div class="multi_row">
                            <?if(isset($_SESSION['BX_DELIVERY_MULTI_NAME_CHECKED'])):?>
                                <div class="delivery-multi_check">
                                    <?=$_SESSION['BX_DELIVERY_MULTI_NAME_CHECKED'];?>
                                </div>
                            <?endif;?>
                            <?foreach($arResult['DELIVERIES'] as $delivery):?>
                                <?if($delivery['CHECKED'] == 'Y' && $delivery['DATA']['MULTIVARIANT'] == 1):
                                    $deliveryChecked = $delivery['DATA']['DELIVERY_VARIANTS'];
                                endif;?>
                            <?endforeach;?>
                            <?if($checkedMulti == true && !empty($deliveryChecked)):?>
                            <div class="delivery-multi_btn">
                                Выбрать пункт самовывоза
                            </div>
                            <?endif;?>
                            <div class="delivery-map" id="popupMap">
                                <?include('delivery_map.php');?>
                            </div>
                            </div>
                        </div>
                    <?endif;?>
                    <?if($deliveryTabActive == false){?>
                        <div class="delivery-not_item delivery-list_tab active">Выберите способ доставки</div>
                    <?}?>
                </div>
            </div>
            <input type="submit" id="delivery-form_submit" style="display:none;">
        </form>
        <form id="delivery-form_multi" action="<?=POST_FORM_ACTION_URI?>" method="POST" name="DELIVERY_MULTI" enctype="multipart/form-data">
            <?=bitrix_sessid_post()?>
            <input type="hidden" name="action" value="delivery_multy">
            <input type="hidden" name="variant_id" value="1">
            <input type="hidden" name="variant_name" value="2">
            <input type="submit" id="delivery-multi_submit" style="display:none;">
        </form>
    <?else:?>
        <div class="checkout-shipment_not">
            Укажите адрес для выбора доставки
        </div>
    <?endif;?>
</div>

<script>
    BX.ready(function(){
        popupMap.setContent(BX('popupMap'));
        BX.bindDelegate(
            document.body, 'click', {className: 'delivery-multi_btn' },
            function(e){
                popupMap.show();
                popupMap.adjustPosition();
            }
        );
    });
</script>