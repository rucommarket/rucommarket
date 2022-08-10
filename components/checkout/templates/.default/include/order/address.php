<div class="checkout-delivery_address">
    <h3>Адрес</h3>
    <form id="address-form_change" class="checkout-delivery_form address" action="<?=POST_FORM_ACTION_URI?>" method="POST" name="ADDRESS_INPUT" enctype="multipart/form-data">
        <?=bitrix_sessid_post()?>
        <input type="hidden" name="action" value="check_address">
        <div class="checkout-delivery_form_row">
            <div style="width: 100%">
                <input type="text" id="address_string" name="address_string" placeholder="Введите адрес *"<?
                    if(isset($arResult['ADDRESS']['STRING']) && !empty($arResult['ADDRESS']['STRING'])) {?>
                    value="<?=$arResult['ADDRESS']['STRING']?>"
                    <?}
                    ?>>
                <span id="error_code_address"></span>
            </div>
        </div>
            <input type="hidden" name="address_lat" <?
                if(isset($arResult['ADDRESS']['LAT'])) {?>
                value="<?=$arResult['ADDRESS']['LAT']?>"
                <?}
                ?>>
            <input type="hidden" name="address_lon" <?
                if(isset($arResult['ADDRESS']['LON'])) {?>
                value="<?=$arResult['ADDRESS']['LON']?>"
                <?}
                ?>>
            <input type="hidden" name="address_kladr" <?
                if(isset($arResult['ADDRESS']['KLADR'])) {?>
                value="<?=$arResult['ADDRESS']['KLADR']?>"
                <?}
                ?>>
            <input type="hidden" name="address_index" <?
                if(isset($arResult['ADDRESS']['INDEX'])) {?>
                value="<?=$arResult['ADDRESS']['INDEX']?>"
                <?}
                ?>>
            <input type="hidden" name="address_region" <?
                if(isset($arResult['ADDRESS']['REGION'])) {?>
                value="<?=$arResult['ADDRESS']['REGION']?>"
                <?}
                ?>>
            <input type="hidden" name="address_city" <?
                if(isset($arResult['ADDRESS']['CITY'])) {?>
                value="<?=$arResult['ADDRESS']['CITY']?>"
                <?}
                ?>>
            <input type="hidden" name="address_street" <?
                if(isset($arResult['ADDRESS']['STREET'])) {?>
                value="<?=$arResult['ADDRESS']['STREET']?>"
                <?}
                ?>>
            <input type="hidden" name="address_house" <?
                if(isset($arResult['ADDRESS']['HOUSE'])) {?>
                value="<?=$arResult['ADDRESS']['HOUSE']?>"
                <?}
                ?>>
            <input type="hidden" name="address_building" <?
                if(isset($arResult['ADDRESS']['BUILDING'])) {?>
                value="<?=$arResult['ADDRESS']['BUILDING']?>"
                <?}
                ?>>
            <input type="hidden" name="address_flat" <?
                if(isset($arResult['ADDRESS']['FLAT'])) {?>
                value="<?=$arResult['ADDRESS']['FLAT']?>"
                <?}
                ?>>
        <div class="checkout-delivery_form_row">
            <textarea maxlength="250" name="address_comment" placeholder="Комментарий, если необходимо"><?
                if(isset($arResult['ADDRESS']['COMMENT']) && !empty($arResult['ADDRESS']['COMMENT'])) {
                echo trim($arResult['ADDRESS']['COMMENT']);
                }
                ?></textarea>
        </div>
        <input type="submit" id="address-form_submit" style="display:none;">
    </form>
</div>