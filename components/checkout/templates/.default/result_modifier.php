<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arProductIds = [];
$tags = [];
foreach ($arResult['BASKET']['ITEMS'] as $arItem) {
    $arProductIds[$arItem['PRODUCT']['IBLOCK_ID']][] = $arItem['PRODUCT_ID'];
}
foreach($arResult['GIFTS_COLLECTION'] as $collection_gift) {
    foreach($collection_gift as $id_gift => $gift) {
        $arProductIds[$gift['IBLOCK_ID']][] = $id_gift;
    }
}

if (!empty($arProductIds)) {
    foreach($arProductIds as $iblockId => $arProduct) {
        $arProduct = array_unique($arProduct);
        $elements = CIBlockElement::GetList([], ["IBLOCK_ID" => $iblockId, "ID" => $arProduct], false, false, ["ID", "TAGS", "PROPERTY_MORE_PHOTO"]);
        while ($el = $elements->Fetch()) {
            if($el['PROPERTY_MORE_PHOTO_VALUE'])
                $arResult['BASKET_ITEMS_PHOTOS'][$el['ID']] = CFile::GetPath($el['PROPERTY_MORE_PHOTO_VALUE'][0]);

            $tags = array_merge($tags, explode(",", $el['TAGS']));
        }
    }
}

//удаляем DiGift из платежных систем
if(isset($arResult['PAYMENTS'][13]))
    unset($arResult['PAYMENTS'][13]);