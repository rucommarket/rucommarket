<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->RestartBuffer();
header('Content-Type: application/json');
echo \Bitrix\Main\Web\Json::encode($arResult,JSON_UNESCAPED_UNICODE);
die();