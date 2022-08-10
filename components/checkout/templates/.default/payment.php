<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(false);
?>

<section class="checkout">
    <h1 class="checkout-header">Оплата заказа</h1>
    <?$APPLICATION->IncludeComponent(
	"bitrix:sale.order.payment",
	"",
	Array(
		"COMPOSITE_FRAME_MODE" => "A",
		"COMPOSITE_FRAME_TYPE" => "DYNAMIC_WITH_STUB",
    ),
    $component
);?>
</section>