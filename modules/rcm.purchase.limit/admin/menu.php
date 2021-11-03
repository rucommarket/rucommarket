<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("rcm.purchase.limit");

if($POST_RIGHT =="W")
{
	$aMenu = array(
		"parent_menu" => "global_menu_marketing",
		"section" => "rcm.purchase.limit",
		"sort" => 500,
		"text" => Loc::getMessage("RCM_PURCHASE_LIMIT_menu_sect"),
		"title" => Loc::getMessage("RCM_PURCHASE_LIMIT_menu_sect_title"),
		"icon" => "sale_menu_icon_buyers",
		"page_icon" => "subscribe_page_icon",
		"items_id" => "rcm_purchase_limit",
		"items" => array(
			array(
				"text" => Loc::getMessage("RCM_PURCHASE_LIMIT_list"),
				"url" => "rcm_purchase_limit.php?lang=".LANGUAGE_ID,
				"more_url" => array("rcm_purchase_limit.php","rcm_purchase_limit_edit.php"),
				"title" => Loc::getMessage("RCM_PURCHASE_LIMIT_list_title")
			),
		)
	);

	return $aMenu;
}
return false;
?>