<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ac.api/include.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;

Loc::loadMessages(__FILE__);

$module_id = "ac.api";

$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);

if($POST_RIGHT=="D")
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$request = HttpApplication::getInstance()->getContext()->getRequest();

\Bitrix\Main\Loader::includeModule($module_id);
$sTableID = "tbl_projects";

$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

$FilterArr = array(
	"find",
	"find_type",
	"find_id",
);

$lAdmin->InitFilter($FilterArr);

$arFilter = array();

$arFilter = array(
    "ID" => ($find!="" && $find_type == "id"? $find:$find_id),
    "NAME" => ($find!="" && $find_type == "name"? $find:$find_name),
    "DATE_CREATE_FROM" => $find_date_create_from,
    "DATE_CREATE_TO" => $find_date_create_to,
);

if($lAdmin->EditAction() && $POST_RIGHT=="W")
{
	foreach($FIELDS as $ID=>$arFields)
	{
		if(!$lAdmin->IsUpdated($ID))
			continue;
		
	}
}

if(($request->get("action_button") == "delete" || $request->get("action") == "delete") && $POST_RIGHT=="W" && check_bitrix_sessid()) {
	$getId = $request->get("ID");
	if(is_array($getId)) {
		foreach($getId as $elId) {
			\AC\Api\Projects::deleteProject($elId);
		}
	} elseif($getId>0) {
		\AC\Api\Projects::deleteProject($getId);
	}
}
if(($request->get("action_button") == "deactive" || $request->get("action") == "deactive") && $POST_RIGHT=="W" && check_bitrix_sessid()) {
	$getId = $request->get("ID");
	if(is_array($getId)) {
		foreach($getId as $elId) {
			\AC\Api\Projects::updateProject($elId,['ACTIVE'=>'N']);
		}
	} elseif($getId>0) {
		\AC\Api\Projects::updateProject($getId,['ACTIVE'=>'N']);
	}
}
if(($request->get("action_button") == "active" || $request->get("action") == "active") && $POST_RIGHT=="W" && check_bitrix_sessid()) {
	$getId = $request->get("ID");
	if(is_array($getId)) {
		foreach($getId as $elId) {
			\AC\Api\Projects::updateProject($elId,['ACTIVE'=>'Y']);
		}
	} elseif($getId>0) {
		\AC\Api\Projects::updateProject($getId,['ACTIVE'=>'Y']);
	}
}

$lAdmin->AddHeaders(array(
	array(
		"id" => "ID",
		"content" => "ID",
		"sort" => "ID",
		"align" => "right",
		"default" => true,
	),
	array(
		"id" => "ACTIVE",
		"content" => Loc::getMessage("AC_API_ADMIN_PROJECTS_ACTIVE"),
		"sort" => "ACTIVE",
		"default" => true,
	),
	array(
		"id" => "NAME",
		"content" => Loc::getMessage("AC_API_ADMIN_PROJECTS_NAME"),
		"sort" => "NAME",
		"default" => true,
	),
    array(
		"id" => "CODE",
		"content" => Loc::getMessage("AC_API_ADMIN_PROJECTS_CODE"),
		"sort" => "CODE",
		"default" => true,
	),
	array(
		"id" => "IP_ADDRESS",
		"content" => Loc::getMessage("AC_API_ADMIN_PROJECTS_IP_ADDRESS"),
		"sort" => "IP_ADDRESS",
		"default" => true,
	),
	array(
		"id" => "DATE_CREATE",
		"content" => Loc::getMessage("AC_API_ADMIN_PROJECTS_DATE_CREATE"),
		"sort" => "DATE_CREATE",
		"default" => true,
	),
	array(
		"id" => "CREATED_BY_USER",
		"content" => Loc::getMessage("AC_API_ADMIN_PROJECTS_CREATED_BY"),
		"sort" => "CREATED_BY",
		"default" => false,
	),
));

$arVisibleColumns = $lAdmin->GetVisibleHeaderColumns();

if($_REQUEST["mode"] == "excel") {
	$usePageNavigation = false;
}
else {
	$navyParams = CDBResult::GetNavParams(CAdminResult::GetNavSize(
		$sTableID,
		array('nPageSize' => 20, 'sNavID' => $APPLICATION->GetCurPage())
	));
	$usePageNavigation = true;
}

if (!isset($by))
	$by = 'id';
if (!isset($order))
	$order = 'desc';


$getListParams = [
	'select' => [
		'ID',
		'*',
		'CREATED_BY_LOGIN'=>'CREATED_BY_USER.LOGIN',
		'CREATED_BY_NAME'=>'CREATED_BY_USER.NAME',
		'CREATED_BY_LAST_NAME'=>'CREATED_BY_USER.LAST_NAME',
	],
	'filter' => $arFilter,
	'order' => [$by => $order]
];

if ($usePageNavigation) {
	$rsDataCount = \AC\Api\Projects::getCount($arFilter);
}

if ($usePageNavigation)
{
	$getListParams['limit'] = $navyParams['SIZEN'];
	$getListParams['offset'] = $navyParams['SIZEN']*($navyParams['PAGEN']-1);
}

$rsData = \AC\Api\Projects::getProjects($getListParams);
$rsData = new CAdminResult($rsData, $sTableID);


if ($usePageNavigation)
{
	$rsData->NavStart($getListParams['limit'], $navyParams['SHOW_ALL'], $navyParams['PAGEN']);
	$rsData->NavRecordCount = $rsDataCount;
	$rsData->NavPageCount = ceil($rsDataCount/$navyParams['SIZEN']);
	$rsData->NavPageNomer = $navyParams['PAGEN'];
} else {
	$rsData->NavStart();
}
$lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage("post_nav")));

while($arRes = $rsData->NavNext(true, "f_")):
	$row =& $lAdmin->AddRow($f_ID, $arRes);
	$row->AddViewField("ACTIVE", ($f_ACTIVE=='Y') ? Loc::getMessage("AC_API_ADMIN_PROJECTS_ACTIVE_TRUE") : Loc::getMessage("AC_API_ADMIN_PROJECTS_ACTIVE_FALSE"));
	$row->AddViewField("CREATED_BY_USER", $f_CREATED_BY_NAME.' '.$f_CREATED_BY_LAST_NAME.' <a href="user_edit.php?ID='.$f_CREATED_BY.'&amp;lang='.LANG.'">'.'('.$f_CREATED_BY_LOGIN.')</a>');


    if($POST_RIGHT=="W") {
		$arActions = array();
		$viewUrl = $selfFolderUrl."ac_api_projects_view.php?ID=".$f_ID."lang=".LANG;
		$editUrl = $selfFolderUrl."ac_api_projects_edit.php?ID=".$f_ID."lang=".LANG;
		$arActions[] = array(
			"ICON" => "view",
			"TEXT" => Loc::getMessage("AC_API_ADMIN_PROJECTS_ACTION_VIEW"),
			"LINK" => $viewUrl,
			"DEFAULT" => true
		);
		$arActions[] = array(
			"ICON" => "edit",
			"TEXT" => Loc::getMessage("AC_API_ADMIN_PROJECTS_ACTION_EDIT"),
			"LINK" => $editUrl,
		);
		if($f_ACTIVE=='Y') {
			$arActions[] = array(
				"TEXT" => Loc::getMessage("AC_API_ADMIN_PROJECTS_ACTION_DEACTIVE"),
				"ACTION" => $lAdmin->ActionDoGroup($f_ID, "deactive")
			);
		} else {
			$arActions[] = array(
				"TEXT" => Loc::getMessage("AC_API_ADMIN_PROJECTS_ACTION_ACTIVE"),
				"ACTION" => $lAdmin->ActionDoGroup($f_ID, "active")
			);
		}
		$arActions[] = array(
			"ICON" => "delete",
			"TEXT" => Loc::getMessage("AC_API_ADMIN_PROJECTS_ACTION_DELETE"),
			"ACTION" => "if(confirm('".GetMessage('DELETE_CONTRACTOR_CONFIRM')."')) ".$lAdmin->ActionDoGroup($f_ID, "delete")
		);

		$row->AddActions($arActions);
	}
endwhile;


$lAdmin->AddFooter(
	array(
		array("title"=>Loc::getMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
		array("counter"=>true, "title"=>Loc::getMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
	)
);

$actionList = [];
if($POST_RIGHT=="W") {
    $actionList["delete"] = GetMessage("MAIN_ADMIN_LIST_DELETE");
	$actionList["active"] = GetMessage("MAIN_ADMIN_LIST_ACTIVATE");
	$actionList["deactive"] = GetMessage("MAIN_ADMIN_LIST_DEACTIVATE");
}

if($POST_RIGHT == "W")
$lAdmin->AddGroupActionTable($actionList,["disable_action_target"=>true]);

$aContext = array(
	array(
		"TEXT"=>Loc::getMessage("MAIN_ADD"),
		"LINK"=>"ac_api_projects_edit.php?lang=".LANG,
		"TITLE"=>Loc::getMessage("AC_API_ADMIN_PROJECTS_ADD_TITLE"),
		"ICON"=>"btn_new",
	),
);
$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage("AC_API_ADMIN_PROJECTS_TITLE"));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$oFilter = new CAdminFilter(
	$sTableID."_filter",
	array(
		"id" => Loc::getMessage("AC_API_ADMIN_PROJECTS_FIND_ID"),
		"name" => Loc::getMessage("AC_API_ADMIN_PROJECTS_FIND_NAME"),
		"date_create" => Loc::getMessage("AC_API_ADMIN_PROJECTS_FIND_DATE_CREATE"),
	)
);
?>

<form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
<?
$oFilter->Begin();
?>
<tr>
	<td><b><?=Loc::getMessage("AC_API_ADMIN_PROJECTS_FIND")?>:</b></td>
	<td>
		<input type="text" size="25" name="find" value="<?echo htmlspecialcharsbx($find)?>" title="<?=Loc::getMessage("AC_API_ADMIN_PROJECTS_FIND_TITLE")?>">
		<?
		$arr = array(
			"reference" => array(
				Loc::getMessage("AC_API_ADMIN_PROJECTS_FIND_ID"),
				Loc::getMessage("AC_API_ADMIN_PROJECTS_FIND_NAME"),
				Loc::getMessage("AC_API_ADMIN_PROJECTS_FIND_DATE_CREATE"),
			),
			"reference_id" => array(
				"id",
				"name",
				"date_create"
			)
		);
		echo SelectBoxFromArray("find_type", $arr, $find_type, "", "");
		?>
	</td>
</tr>
<tr>
	<td><?=Loc::getMessage("AC_API_ADMIN_PROJECTS_FIND_ID")?>:</td>
	<td>
		<input type="text" name="find_id" size="47" value="<?echo htmlspecialcharsbx($find_id)?>">
		&nbsp;
	</td>
</tr>
<tr>
	<td><?=Loc::getMessage("AC_API_ADMIN_PROJECTS_FIND_NAME")?>:</td>
	<td>
		<input type="text" name="find_name" size="47" value="<?echo htmlspecialcharsbx($find_name)?>">
		&nbsp;
	</td>
</tr>
<tr>
	<td><?echo Loc::getMessage("AC_API_ADMIN_PROJECTS_FIND_DATE_CREATE")." (".FORMAT_DATE."):"?></td>
	<td><?echo CalendarPeriod("find_date_create_from", $find_date_create_from, "find_date_create_to", $find_date_create_to, "find_form","Y")?></td>
</tr>

<?
$oFilter->Buttons(array("table_id"=>$sTableID,"url"=>$APPLICATION->GetCurPage(), "form" => "find_form"));
$oFilter->End();
?>
</form>

<?$lAdmin->DisplayList();?>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
