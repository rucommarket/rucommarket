<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arProductIds = [];
$tags = [];
foreach ($arResult['BASKET']['ITEMS'] as $arItem) {
    $arProductIds[] = $arItem['PRODUCT_ID'];
}
if (!empty($arProductIds)) {
    $arProductIds = array_unique($arProductIds);
    $elements = CIBlockElement::GetList([], ["ID" => $arProductIds], false, false, ["ID", "TAGS", "PROPERTY_MORE_PHOTO"]);
    while ($el = $elements->Fetch()) {
        $arResult['BASKET_ITEMS_PHOTOS'][$el['ID']] = CFile::GetPath($el['PROPERTY_MORE_PHOTO_VALUE'][0]);
        $tags = array_merge($tags, explode(",", $el['TAGS']));
    }
}

//удаляем DiGift из платежных систем
if(isset($arResult['PAYMENTS'][13]))
    unset($arResult['PAYMENTS'][13]);