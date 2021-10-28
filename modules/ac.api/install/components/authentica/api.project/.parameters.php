<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = array(
    "PARAMETERS" => array(
		"SEF_MODE" => array(
			"method" => array(
				"NAME" => "Метод",
				"DEFAULT" => "#METHOD#",
				"VARIABLES" => array(
                    "PROJECT",
					"METHOD",
				),
			),
		),
		"CACHE_TIME"  =>  array("DEFAULT"=>36000000),
    ),
);
?>