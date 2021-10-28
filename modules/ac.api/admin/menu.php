<?
IncludeModuleLangFile(__FILE__);

if($APPLICATION->GetGroupRight("ac.api") >= "R")
{
	$aMenu = array(
		"parent_menu" => "global_menu_services",
		"section" => "ac_partners",
		"sort" => 500,
		"text" => GetMessage("AC_API_PARTNERS"),
		"title"=> GetMessage("AC_API_PARTNERS_ALT"),
		"icon" => "scale_menu_icon",
		"page_icon" => "scale_page_icon",
		"items_id" => "menu_ac_partners",
		"items" => array(
			array(
				"text" => GetMessage("AC_API_PARTNERS_LIST_PROJECTS"),
				"url" => "ac_api_projects.php?lang=".LANGUAGE_ID,
				"more_url" => array("ac_api_projects.php","ac_api_projects_edit.php","ac_api_projects_view.php"),
				"title" => GetMessage("AC_API_PARTNERS_LIST_PROJECTS_ALT")
			),
		)
	);

	return $aMenu;
}
return false;
?>
