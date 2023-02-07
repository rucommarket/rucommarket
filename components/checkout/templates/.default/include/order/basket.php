<?
use \Bitrix\Main\Localization\Loc;
?>
<?if(!empty($arResult['BASKET']['ITEMS'])) {?>
<div class="checkout-cart_container">
    <?foreach($arResult['BASKET']['ITEMS'] as $item):?>
        <?if(!$item['CAN_BUY']) continue;?>
        <a <?if(!$item['PROPERTIES']['IS_GIFT'] || $item['PROPERTIES']['IS_GIFT']['VALUE']!='Y'){?>href="<?=$item['PRODUCT']['URL']?>" target="_blank"<?}?> class="checkout-cart_row checkout-cart_item" id="checkout-cart_row-basket-<?=$item['ID']?>">
            <div class="cart-item_img">
                <?if(!empty($arResult['BASKET_ITEMS_PHOTOS'][$item['PRODUCT_ID']])){?>
                    <div class="cart-product__image"
                        style="background-image: url('<?=$arResult['BASKET_ITEMS_PHOTOS'][$item['PRODUCT_ID']]?>')">
                        <span><?=$item['QUANTITY']?></span>
                    </div>
                <?}?>
            </div>
            <div class="cart-item_description">
                <span class="cart-item__title"><?=$item['PRODUCT']['NAME']?></span>
            </div>
            <div class="cart-item_summa">
                <span id="cart-item_summa-<?=$item['ID']?>"><?=rtrim(rtrim(number_format(($item['FINAL_PRICE']),2,'.',' '),'0'),'.')?></span> ₽
            </div>
        </a>
    <?endforeach;?>
</div>
<?}?>