<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rcm.purchase.limit/include.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\HttpApplication;

\Bitrix\Main\Loader::includeModule('iblock');
Loc::loadMessages(__FILE__);


$POST_RIGHT = $APPLICATION->GetGroupRight("rcm.purchase.limit");


if($POST_RIGHT=="D")
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = "rcm.purchase.limit";
$sTableID = "tbl_selections";


$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);


$FilterArr = array(
	"find",
	"find_type",
	"find_id",
	"find_user_id",
	"find_limit",
);

$lAdmin->InitFilter($FilterArr);
$arFilter = array();

$arFilter = array(
	"ID" => $find_id,
	"USER_ID" => $find_user_id,
	"LIMIT" => $find_limit,
	">=DATE_CREATE" => $find_date_create_from,
	"<=DATE_CREATE" => $find_date_create_to,
);
$arFilter = array_diff($arFilter, array(''));


if($lAdmin->EditAction() && $POST_RIGHT=="W")
{
	foreach($FIELDS as $ID=>$arFields)
	{
		if(!$lAdmin->IsUpdated($ID))
			continue;
		
	}
}

if($request->get("action_button") == "delete" && $POST_RIGHT=="W" && check_bitrix_sessid()) {
	$getId = $request->get("ID");
	if($getId>0)
		\RCM\Purchase\Limit\Internals\UsersTable::delete($request->get("ID"));
}
if($lAdmin->EditAction() && $POST_RIGHT=="W")
{
	foreach($FIELDS as $ID=>$arFields)
	{
		if(!$lAdmin->IsUpdated($ID))
			continue;
		
	}
}

$lAdmin->AddHeaders(array(
	array(
		"id" => "ID",
		"content" => Loc::getMessage("rcm_purchase_limit_column_id"),
		"sort" => "ID",
		"default" => true,
	),
	array(
		"id" => "ACTIVE",
		"content" => Loc::getMessage("rcm_purchase_limit_column_active"),
		"sort" => "ACTIVE",
		"default" => true,
	),
	array(
		"id" => "USER_ID",
		"content" => Loc::getMessage("rcm_purchase_limit_column_user"),
		"sort" => "USER_ID",
		"default" => true,
	),
	array(
		"id" => "LIMIT",
		"content" => Loc::getMessage("rcm_purchase_limit_column_limit"),
		"sort" => "LIMIT",
		"default" => true,
	),
	array(
		"id" => "DATE_CREATE",
		"content" => Loc::getMessage("rcm_purchase_limit_column_date_create"),
		"sort" => "date_create",
		"default" => false,
	),
	array(
		"id" => "CREATED_BY_USER",
		"content" => Loc::getMessage("rcm_purchase_limit_column_created_by"),
		"sort" => "created_by",
		"default" => false,
	),
	array(
		"id" => "TIMESTAMP_X",
		"content" => Loc::getMessage("rcm_purchase_limit_column_timestamp"),
		"sort" => "timestamp",
		"default" => false,
	),
	array(
		"id" => "MODIFIED_BY_USER",
		"content" => Loc::getMessage("rcm_purchase_limit_column_modified_by"),
		"sort" => "modified_by",
		"default" => false,
	),
));

$arVisibleColumns = $lAdmin->GetVisibleHeaderColumns();
if($_REQUEST["mode"] == "excel")
	$arNavParams = false;
else
	$arNavParams = array("nPageSize"=>CAdminResult::GetNavSize($sTableID));

$rsData = \RCM\Purchase\Limit\Internals\UsersTable::getList([
	'select' => [
		'*',
		'USER_LOGIN'=>'USER.LOGIN',
		'USER_NAME' => 'USER.NAME',
		'USER_LAST_NAME' => 'USER.LAST_NAME',
		'CREATED_BY_LOGIN'=>'CREATED_BY_USER.LOGIN',
		'CREATED_BY_NAME'=>'CREATED_BY_USER.NAME',
		'CREATED_BY_LAST_NAME'=>'CREATED_BY_USER.LAST_NAME',
		'MODIFIED_BY_LOGIN'=>'MODIFIED_BY_USER.LOGIN',
		'MODIFIED_BY_NAME'=>'MODIFIED_BY_USER.NAME',
		'MODIFIED_BY_LAST_NAME'=>'MODIFIED_BY_USER.LAST_NAME',
	],
	'filter' => $arFilter
]);

$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage("post_nav")));

while($arRes = $rsData->NavNext(true, "f_")):
	//print_r('<pre>');print_r($arRes);print_r('</pre>');die();
	$row =& $lAdmin->AddRow($f_ID, $arRes);
	$row->AddViewField("ACTIVE", ($f_ACTIVE=='Y')?'Да':'Нет');
	$row->AddViewField("CREATED_BY_USER", $f_CREATED_BY_NAME.' '.$f_CREATED_BY_LAST_NAME.' <a href="user_edit.php?ID='.$f_CREATED_BY.'&amp;lang='.LANG.'">'.'('.$f_CREATED_BY_LOGIN.')</a>');
	$row->AddViewField("MODIFIED_BY_USER", $f_MODIFIED_BY_NAME.' '.$f_MODIFIED_BY_LAST_NAME.' <a href="user_edit.php?ID='.$f_MODIFIED_ID.'&amp;lang='.LANG.'">'.'('.$f_MODIFIED_BY_LOGIN.')</a>');
	
	$row->AddViewField("USER_ID", ($f_USER_ID)?$f_USER_NAME.' '.$f_USER_LAST_NAME.' <a href="user_edit.php?ID='.$f_USER_ID.'&amp;lang='.LANG.'">'.'('.$f_USER_LOGIN.')</a>':'Любой');
	$row->AddViewField("LIMIT", $f_LIMIT);
	
	unset($list_prods);

	if($POST_RIGHT=="W") {
		$arActions = array();
		$editUrl = $selfFolderUrl."rcm_purchase_limit_edit.php?ID=".$f_ID."lang=".LANG;
		//$editUrl = $adminSidePanelHelper->editUrlToPublicPage($editUrl);
		$arActions[] = array(
			"ICON" => "edit",
			"TEXT" => Loc::getMessage("rcm_purchase_limit_action_edit"),
			"LINK" => $editUrl,
			"DEFAULT" => true
		);
		$arActions[] = array(
			"ICON" => "delete",
			"TEXT" => Loc::getMessage("rcm_purchase_limit_action_delete"),
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
if($POST_RIGHT == "W")
$lAdmin->AddGroupActionTable(Array(
	"delete"=>Loc::getMessage("MAIN_ADMIN_LIST_DELETE"),
	));

$aContext = array(
	array(
		"TEXT"=>Loc::getMessage("MAIN_ADD"),
		"LINK"=>"rcm_purchase_limit_edit.php?lang=".LANG,
		"TITLE"=>Loc::getMessage("rcm_purchase_limit_add_title"),
		"ICON"=>"btn_new",
	),
);
$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage("rcm_purchase_limit_title"));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$oFilter = new CAdminFilter(
	$sTableID."_filter",
	array(
		"id" => Loc::getMessage("rcm_purchase_limit_find_id"),
		"user_id" => Loc::getMessage("rcm_purchase_limit_find_user_id"),
		"limit" => Loc::getMessage("rcm_purchase_limit_find_limit"),
		"date_create" => Loc::getMessage("rcm_purchase_limit_find_date_create"),
	)
);
?>

<form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
<?
$oFilter->Begin();
?>
<tr>
	<td><?=Loc::getMessage("rcm_purchase_limit_find_id")?>:</td>
	<td>
		<input type="text" name="find_id" size="47" value="<?echo htmlspecialcharsbx($find_id)?>">
		&nbsp;
	</td>
</tr>
<tr>
	<td><?=Loc::getMessage("rcm_purchase_limit_find_user_id")?>:</td>
	<td>
		<input type="text" name="find_user_id" size="47" value="<?echo htmlspecialcharsbx($find_user_id)?>">
		&nbsp;
	</td>
</tr>
<tr>
	<td><?=Loc::getMessage("rcm_purchase_limit_find_limit")?>:</td>
	<td>
		<input type="text" name="find_limit" size="47" value="<?echo htmlspecialcharsbx($find_limit)?>">
		&nbsp;
	</td>
</tr>
<tr>
	<td><?echo Loc::getMessage("rcm_purchase_limit_find_date_create")." (".FORMAT_DATE."):"?></td>
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
