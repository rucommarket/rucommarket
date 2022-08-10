<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Loader;
use Bitrix\Iblock;
use Bitrix\Catalog;

if (Loader::includeModule('catalog')) {
    $arIblockNames = array();
	$parameters = array(
		'select' => array('IBLOCK_ID', 'NAME' => 'IBLOCK.NAME'),
		'order' => array('IBLOCK_ID' => 'ASC'),
	);
    $catalogIterator = Catalog\CatalogIblockTable::getList($parameters);
    while ($catalog = $catalogIterator->fetch())
	{
		$catalog['IBLOCK_ID'] = (int)$catalog['IBLOCK_ID'];
		$arIblockIDs[] = $catalog['IBLOCK_ID'];
		$arIblockNames[$catalog['IBLOCK_ID']] = $catalog['NAME'];
	}
    unset($catalog, $catalogIterator);
    $arProps = array();
	$propertyIterator = Iblock\PropertyTable::getList(array(
		'select' => array('ID', 'CODE', 'NAME', 'IBLOCK_ID'),
		'filter' => array('@IBLOCK_ID' => $arIblockIDs, '=ACTIVE' => 'Y', '!=XML_ID' => CIBlockPropertyTools::XML_SKU_LINK),
		'order' => array('IBLOCK_ID' => 'ASC', 'SORT' => 'ASC', 'ID' => 'ASC')
	));
	while ($property = $propertyIterator->fetch()) {
        $name = $property['NAME'];
        $iblock[$name][] = $arIblockNames[$property['IBLOCK_ID']];
        $arProps[$property['CODE']] = $name.' ('.implode(',',$iblock[$name]).')';
    }
    unset($propertyIterator,$name,$iblock);
}


$arProperties = [];
$dbProperties = 

$arComponentParameters = array(
	"PARAMETERS" => array(
		"AJAX_MODE" => array(),
		"SEF_MODE" => array(
			"cart" => array(
				"NAME" => GetMessage("RCM_CHECKOUT_PARAMS_CART"),
				"DEFAULT" => "",
				"VARIABLES" => array(
				),
			),
			"order" => array(
				"NAME" => GetMessage("RCM_CHECKOUT_PARAMS_ORDER"),
				"DEFAULT" => "order/",
				"VARIABLES" => array(
				),
			),
			"payment" => array(
				"NAME" => GetMessage("RCM_CHECKOUT_PARAMS_PAYMENT"),
				"DEFAULT" => "payment/",
				"VARIABLES" => array(
				),
			),
		),
        "COLUMN_LIST" => array(
            "NAME" => GetMessage("RCM_CHECKOUT_PARAMS_COLUMN_LIST"),
            "TYPE" => "LIST",
            "ADDITIONAL_VALUES" => "N",
            "MULTIPLE" => "Y",
            "VALUES" => [
                "NAME" => GetMessage("RCM_CHECKOUT_PARAMS_COLUMN_LIST_NAME"),
                "PRICE" => GetMessage("RCM_CHECKOUT_PARAMS_COLUMN_LIST_PRICE"),
                "QUANTITY" => GetMessage("RCM_CHECKOUT_PARAMS_COLUMN_LIST_QUANTITY"),
                "DELETE" => GetMessage("RCM_CHECKOUT_PARAMS_COLUMN_LIST_DELETE"),
                "SUMM" => GetMessage("RCM_CHECKOUT_PARAMS_COLUMN_LIST_SUMM"),
            ],
            "DEFAULT" => [
                "NAME",
                "PRICE",
                "QUANTITY",
                "SUMM",
            ]
        ),
        "PRODUCT_PROPERTIES" => array(
            "NAME" => GetMessage("RCM_CHECKOUT_PARAMS_PROPERTIES"),
            "TYPE" => "LIST",
            "ADDITIONAL_VALUES" => "N",
            "MULTIPLE" => "Y",
            "VALUES" => $arProps,
            "DEFAULT" => []
        )
	)
);
