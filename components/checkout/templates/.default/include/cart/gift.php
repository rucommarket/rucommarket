<?
\CJSCore::Init(["popup","owl_carousel"]);
?>
<div class="checkout-cart_gifts" id="checkout-cart_gifts">
    <?foreach($arResult['GIFTS_COLLECTION'] as $key_gift => $collection_gift):?>
        <div class="checkout-cart_gift" data-collection="<?=$key_gift?>">
            <div class="checkout-cart_gift-image" style="background-image:url('<?=$arResult['BASKET_ITEMS_PHOTOS'][array_values($collection_gift)[0]['ID']]?>');">
            </div>
            <div class="checkout-cart_gift-description">
                <div class="checkout-cart_gift-title">
                    Вам доступен подарок
                </div>
                <div class="checkout-cart_gift-link">
                    Выбрать подарок&nbsp;
                    <svg width="25" height="9" viewBox="0 0 25 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M20.2007 0L25 4.5L20.2007 9L19.5188 8.33947L23.1195 4.9633H0V4.0367H23.1195L19.5188 0.660527L20.2007 0Z" fill="#FE4A5B"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="checkout-cart_gift-popup" id="popupGift_<?=$key_gift?>">
            <div class="checkout-cart_gift-popup_title">
                Вам доступен подарок
            </div>
            <div class="<?if($arResult['IS_MOBILE'])
                echo 'checkout-cart_gift-mobile';
            else
                echo 'checkout-cart_gift-owl owl-carousel'
            ?>">
            <?foreach($collection_gift as $gift):?>
                <div class="popupGift_item">
                    <div class="popupGift_item-image" style="background-image: url('<?=$arResult['BASKET_ITEMS_PHOTOS'][$gift['ID']]?>')"></div>
                    <div class="popupGift_item-brand">
                        <?if($gift['IBLOCK_ID'] == 2 || $gift['IBLOCK_ID'] == 28):?>
                            <?=\CIBlockElement::GetProperty($gift['IBLOCK_ID'],$gift['ID'],[],['CODE'=>'BRAND_REF'])->Fetch()['VALUE'];?>
                        <?endif;?>
                    </div>
                    <div class="popupGift_item-name"><?=$gift['NAME']?></div>
                    <div class="popupGift_item-btn">
                        <form action="<?=POST_FORM_ACTION_URI?>" method="POST" name="CHOOSE_BTN" enctype="multipart/form-data" class="popupGift_item-choose">
                            <?=bitrix_sessid_post()?>
                            <input type="hidden" name="action" value="choose_gift">
                            <input type="hidden" name="id_gift" value="<?=$gift['ID']?>">
                            <button type="submit" onclick="popupGift[<?=$key_gift?>].close();">
                                <span>Выбрать</span>
                            </button>
                        </form>
                    </div>
                </div>
            <?endforeach;?>
            </div>
        </div>
    <?endforeach;?>
</div>
<script>
    BX.ready(function(){
    popupGift = [];
    function popupInit(id) {
        popupGift[id] = new BX.PopupWindow(
            "checkout-cart_gift-"+id,                
            window.body, 
            {
                autoHide : true,
                offsetTop : 1,
                offsetLeft : 0,
                lightShadow : true,
                closeIcon: {right: "40px", top: "40px" ,width: "20px", height: "20px"},
                className: 'checkout-cart_gift-popupGift',
                closeByEsc : true,
                overlay: {
                    backgroundColor: '#ccc', opacity: '80'
                }
            }
        );
        popupGift[id].setContent(BX('popupGift_'+id));
    }
    BX.bindDelegate(
        document.body, 'click', {className: 'checkout-cart_gift' },
        function(e){
            var id = this.getAttribute('data-collection');
            if(!popupGift[id] || popupGift[id] === null) {
                popupInit(id);
            }
            popupGift[id].show();
            popupGift[id].adjustPosition();
        }
    );
    $('.checkout-cart_gift-owl').owlCarousel({
        loop: false,
        autoWidth: true,
        center: false,
        margin: 15,
        nav: true,
        navText: [
            '<svg width="10" height="18" viewBox="0 0 10 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.73341 0.263713C9.37827 -0.0879044 8.80253 -0.0879044 8.44799 0.263713L0.266358 8.36412C-0.0887859 8.71574 -0.0887859 9.28576 0.266358 9.63678L8.44799 17.7372C8.62738 17.913 8.8595 18 9.09161 18C9.32373 18 9.55584 17.913 9.73341 17.7354C10.0886 17.3838 10.0886 16.8137 9.73341 16.4627L2.1954 8.99955L9.73523 1.53458C10.0886 1.18536 10.0886 0.612931 9.73341 0.263713Z" fill="#A7A6A6"/></svg>',
            '<svg width="10" height="18" viewBox="0 0 10 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.266585 0.263713C0.621729 -0.0879044 1.19747 -0.0879044 1.55201 0.263713L9.73364 8.36412C10.0888 8.71574 10.0888 9.28576 9.73364 9.63678L1.55201 17.7372C1.37262 17.913 1.1405 18 0.908389 18C0.676272 18 0.444158 17.913 0.266585 17.7354C-0.0885582 17.3838 -0.0885582 16.8137 0.266585 16.4627L7.8046 8.99955L0.264767 1.53458C-0.0885582 1.18536 -0.0885582 0.612931 0.266585 0.263713Z" fill="#A7A6A6"/></svg>'
        ],
        responsive: {
            0: {
                items: 1
            },
            690: {
                items: 3
            },
            1000: {
                items: 5
            }
        }
    });
});
</script>