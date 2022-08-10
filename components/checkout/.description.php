<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("RCM_CHECKOUT_NAME"),
	"DESCRIPTION" => GetMessage("RCM_CHECKOUT_DESC"),
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "catalog",
			"NAME" => GetMessage("RCM_CHECKOUT")
		)
	),
);
?>