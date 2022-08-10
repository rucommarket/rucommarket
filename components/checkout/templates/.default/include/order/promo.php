<div class="checkout-promo">
    <form id="promo-form_add" action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data">
        <?=bitrix_sessid_post()?>
        <input type="hidden" name="action" value="add_coupon">
        <input name="coupon" placeholder="Промокод" type="text" autocomplete="off">
        <button type="submit">Ввести код</button>
    </form>
    <?if(isset($arResult['COUPONS']) && !empty($arResult['COUPONS'])):?>
        <?foreach($arResult['COUPONS'] as $coupon):?>
            <form id="promo-form_delete-<?=$coupon['ID'];?>" action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data" class="promo-form_delete">
                <input type="hidden" name="action" value="remove_coupon">
                <input type="hidden" name="coupon" value="<?=$coupon['COUPON'];?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 17 16">
                    <path fill="none" stroke="#a7a6a6" stroke-miterlimit="50" d="M8.05.5A7.53 7.53 0 0 0 .5 8c0 4.14 3.38 7.5 7.55 7.5A7.53 7.53 0 0 0 15.6 8c0-4.14-3.38-7.5-7.55-7.5z"></path>
                    <path fill="#a7a6a6" d="M12.14 6.84l-3.86 3.47a.76.76 0 0 1-.32.17c-.1.05-.2.07-.31.07a.77.77 0 0 1-.53-.21L4.74 8.1a.77.77 0 0 1-.03-1.09.77.77 0 0 1 1.09-.03l1.9 1.79 3.41-3.07a.77.77 0 0 1 1.09.05c.28.32.26.8-.06 1.09z"></path>
                </svg>
                Промокод <?=$coupon['COUPON'];?> (<?=$coupon['DISCOUNT_NAME'];?>) активирован
                <button type="submit">
                    <svg viewBox="0 0 20 29">
                        <path d="M4.6 24.01c.02 1.05.88 1.9 1.93 1.9h8.82c1.05 0 1.91-.85 1.92-1.9L18.96 7H3zm11.63-.08v.05c0 .49-.4.89-.88.89H6.53a.89.89 0 0 1-.88-.89L4.15 8.05H17.8z"></path>
                        <path d="M8.58 23.03c.32 0 .58-.26.58-.58V10.58a.58.58 0 0 0-1.16 0v11.87c0 .32.26.58.58.58zM11.58 23.03c.32 0 .58-.26.58-.58V10.58a.58.58 0 0 0-1.16 0v11.87c0 .32.26.58.58.58zM14.58 23.03c.32 0 .58-.26.58-.58V10.58a.58.58 0 0 0-1.16 0v11.87c0 .32.26.58.58.58zM15.65 4.23l.39-2.92L6.29 0 5.9 2.91l-4.31-.58a.542.542 0 0 0-.59.45c-.03.29.17.55.45.59l18.11 2.44h.07c.26 0 .49-.19.52-.45a.52.52 0 0 0-.44-.59zM7.19 1.18l7.67 1.03-.25 1.88-7.67-1.04z"></path>
                    </svg>
                </button>
            </form>
        <?endforeach;?>
    <?endif;?>
</div>