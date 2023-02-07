<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL; 

Loader::includeModule('highloadblock');

$hlblocks = HL\HighloadBlockTable::getList()->fetchAll();
$arHlblocks = [];
foreach($hlblocks as $block):
	$arHlblocks[$block['ID']] = $block['NAME'];
endforeach;

$arComponentParameters = array(
	"PARAMETERS" => array(
		"HLBLOCK_ID" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => "HL-блок",
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "N",
			"VALUES" => $arHlblocks,
			"REFRESH" => "N"
        ),
		"ITEMS_LIMIT" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => "Максимальное количество элементов",
			"TYPE" => "NUMBER",
			"ADDITIONAL_VALUES" => "N",
			"DEFAULT" => 10
        ),
        "CACHE_TIME"  =>  array("DEFAULT"=>36000000),
	),
);
?>