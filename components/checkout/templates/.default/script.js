
BX.ready(function(){
    BX.showWait = function(node, msg) {
        BX.addClass('orderLoading','_visible');
    };
    BX.closeWait = function(node, obMsg) {
        BX.removeClass('orderLoading','_visible');
    };

    BX.bindDelegate(
        document.body, 'change', {tag:'input', className: 'cart-quantity' },
        function(e){
            if(!e) {
               e = window.event;
            }
            var id = this.getAttribute('data-id');
            var quantity = this.value;
            var qEl = this;
            BX.ajax.runComponentAction('rucommarket:checkout','editQuantityCart', {
                mode: 'ajax',
                data: {
                    'id': id,
                    'ajax_basket':'Y',
                    'quantity':quantity
                }
            }).then(function (response) {
                if(response.status === 'success') {
                    BX.onCustomEvent('OnBasketChange');
                    BX('cart-item_summa-'+id).innerHTML = response.data.BASKET_ITEM.FINAL_PRICE.replace(/[,.]?0+$/,'');
                    BX.removeClass(BX('cart-item_summa-'+id),'anim');
                    BX.addClass(BX('cart-item_summa-'+id),'anim');
                    BX('cart-total-price').innerHTML = response.data.BASKET.PRICE.replace(/[,.]?0+$/,'');
                    BX.style(BX('cart-total-price'),'animation','none');
                    BX.style(BX('cart-total-price'),'animation','show_price 1.5s forwards');
                    this.value = response.data.BASKET.QUANTITY;
                }
            }, function (response) {
                response.errors.forEach(function(error){
                    console.log(error.message);
                });
            });
            return BX.PreventDefault(e);
        }
    );

    BX.bindDelegate(
        document.body, 'click', {className: 'cart-quantity_btn' },
        function(e){
            if(!e) {
               e = window.event;
            }
            var event = this.getAttribute('data-event');
            var eventChange = new Event('change',{bubbles:true});
            if(event == "Down") {
                BX.findNextSibling(this,{tag:'input', className: 'cart-quantity' }).stepDown();
                BX.findNextSibling(this,{tag:'input', className: 'cart-quantity' }).dispatchEvent(eventChange);
            }
            if(event == "Up") {
                BX.findPreviousSibling(this,{tag:'input', className: 'cart-quantity' }).stepUp();
                BX.findPreviousSibling(this,{tag:'input', className: 'cart-quantity' }).dispatchEvent(eventChange);
            }
        }
    );
    BX.bindDelegate(
        document.body, 'click', {className: 'delivery-list_tab' },
        function(e){
            if(!e) {
               e = window.event;
            }
            var multi = this.getAttribute('data-multi');
            BX.findChildren(BX('delivery-form_change'), {
                "class" : 'delivery-list_tab'
            },true).forEach(function(element){
                if(element.getAttribute('data-multi') == multi) {
                    if(BX.hasClass(element,'active') == false) BX.addClass(element,'active');
                } else {
                    if(BX.hasClass(element,'active') == true) BX.removeClass(element,'active');
                }
            });
            BX.findChildren(BX('delivery-form_change'), {
                "class" : 'delivery-item'
            },true).forEach(function(element){
                if(element.getAttribute('data-multi') == multi) {
                    if(BX.hasClass(element,'active') == false) BX.addClass(element,'active');
                } else {
                    if(BX.hasClass(element,'active') == true) BX.removeClass(element,'active');
                }
            });
            console.log('1');
        }
    );
});