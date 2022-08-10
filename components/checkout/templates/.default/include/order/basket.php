<?
use \Bitrix\Main\Localization\Loc;
?>
<?if(!empty($arResult["COLUMN_LIST"])) {?>
<div class="checkout-cart_container">
    <div class="checkout-cart_row checkout-cart_header">
        <?foreach($arResult["COLUMN_LIST"] as $idColumn):?>
            <div class="checkout-cart__column checkout-cart_title" id="checkout-cart_col_<?=$idColumn?>">
                <?= Loc::getMessage('RCM_CHECKOUT_CART_COLUMN_LIST_'.$idColumn) ?>
            </div>
        <?endforeach?>
    </div>
    <?foreach($arResult['BASKET']['ITEMS'] as $item):?>
        <div class="checkout-cart_row checkout-cart_item" id="checkout-cart_row-basket-<?=$item['ID']?>">
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
                        <?=$item['PRICE']?> руб.
                        <?if($item['BASE_PRICE'] != $item['PRICE']):?>
                            <span><?=$item['BASE_PRICE']?> руб.</span>
                        <?endif;?>
                    <?} else {?>
                        <span>ПОДАРОК</span>
                    <?}?>
                </div>
            <?}?>
            <?if(in_array("QUANTITY",$arResult["COLUMN_LIST"])){?>
                <div class="checkout-cart__column cart-item_quantity">
                    <input type="number" min="1" max="100" class="cart-quantity" data-id="<?=$item['ID']?>" value="<?=$item['QUANTITY']?>" readonly>
                </div>
            <?}?>
            <?if(in_array("DELETE",$arResult["COLUMN_LIST"])){?>
                <form action="<?=POST_FORM_ACTION_URI?>" method="POST" name="DELIVERY_INPUT" enctype="multipart/form-data" class="checkout-cart__column cart-item_delete">
                    <?=bitrix_sessid_post()?>
                    <input type="hidden" name="action" value="delete_basket">
                    <input type="hidden" name="id_basket" value="<?=$item['ID']?>">
                    <button type="submit">
                        <svg class="checkout-cart-item_delete">
                            <path d="M4.6 24.01c.02 1.05.88 1.9 1.93 1.9h8.82c1.05 0 1.91-.85 1.92-1.9L18.96 7H3zm11.63-.08v.05c0 .49-.4.89-.88.89H6.53a.89.89 0 0 1-.88-.89L4.15 8.05H17.8z"></path>
                            <path d="M8.58 23.03c.32 0 .58-.26.58-.58V10.58a.58.58 0 0 0-1.16 0v11.87c0 .32.26.58.58.58zM11.58 23.03c.32 0 .58-.26.58-.58V10.58a.58.58 0 0 0-1.16 0v11.87c0 .32.26.58.58.58zM14.58 23.03c.32 0 .58-.26.58-.58V10.58a.58.58 0 0 0-1.16 0v11.87c0 .32.26.58.58.58zM15.65 4.23l.39-2.92L6.29 0 5.9 2.91l-4.31-.58a.542.542 0 0 0-.59.45c-.03.29.17.55.45.59l18.11 2.44h.07c.26 0 .49-.19.52-.45a.52.52 0 0 0-.44-.59zM7.19 1.18l7.67 1.03-.25 1.88-7.67-1.04z"></path>
                        </svg>
                    </button>
                </form>
            <?}?>
            <?if(in_array("SUMM",$arResult["COLUMN_LIST"])){?>
                <div class="checkout-cart__column cart-item_summa">
                    <span id="cart-item_summa-<?=$item['ID']?>"><?=$item['FINAL_PRICE']?></span> руб.
                </div>
            <?}?>
        </div>
    <?endforeach;?>
</div>
<?}?>