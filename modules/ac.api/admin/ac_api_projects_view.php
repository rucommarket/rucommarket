<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ac.api/include.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */

global $APPLICATION;
global $DB;
global $USER;
global $USER_FIELD_MANAGER;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\HttpApplication;


CJSCore::Init(['jquery']);

$selfFolderUrl = '/bitrix/admin/';
$listUrl = $selfFolderUrl."ac_api_projects.php?lang=".LANGUAGE_ID;

Loc::loadMessages(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("ac.api");

if($POST_RIGHT=="D")
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
$bReadOnly = false;
if($POST_RIGHT=="R" || $POST_RIGHT=="D")
	$bReadOnly = true;
$request = HttpApplication::getInstance()->getContext()->getRequest();

$id = (!empty($request->get('ID')) ? (int)$request->get('ID') : 0);
if ($id <= 0)
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

ClearVars();

$bVarsFromForm = false;

$userId = (int)$USER->GetID();

$entityId = "Project";


$APPLICATION->SetTitle(str_replace("#ID#", $id, Loc::getMessage("AC_API_PROJECTS_VIEW_TITLE")));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if($id > 0)
{
	$elResult = \AC\Api\Projects::getProjects([
		'select' => [
			'*',
			'CREATED_BY_LOGIN'=>'CREATED_BY_USER.LOGIN',
			'CREATED_BY_NAME'=>'CREATED_BY_USER.NAME',
			'CREATED_BY_LAST_NAME'=>'CREATED_BY_USER.LAST_NAME',
			'MODIFIED_BY_LOGIN'=>'MODIFIED_BY_USER.LOGIN',
			'MODIFIED_BY_NAME'=>'MODIFIED_BY_USER.NAME',
			'MODIFIED_BY_LAST_NAME'=>'MODIFIED_BY_USER.LAST_NAME',
		],
		'filter' => ['ID' => $id],
		'limit' => 1
	]);
	$elResult = $elResult[0];
}

$aMenu = array(
	array(
		"TEXT" => Loc::getMessage("AC_API_PROJECTS_VIEW_LIST"),
		"ICON" => "btn_list",
		"LINK" => $listUrl
	)
);
$context = new CAdminContextMenu($aMenu);
$context->Show();

$actionUrl = $APPLICATION->GetCurPage();

?>
<form enctype="multipart/form-data" method="POST" action="<?=$actionUrl?>" name="ac_api_projects_edit">
	<?echo GetFilterHiddens("filter_");?>
	<input type="hidden" name="lang" value="<?echo LANGUAGE_ID; ?>">
	<input type="hidden" name="ID" value="<?echo $id ?>">
	<?=bitrix_sessid_post()?><?
	$aTabs = array(
		array(
            "DIV" => "edit1",
            "TAB" => Loc::getMessage("AC_API_PROJECTS_VIEW_TAB"),
            "ICON" => "catalog",
            "TITLE" => str_replace("#ID#", $id, Loc::getMessage("AC_API_PROJECTS_VIEW_TAB_DESCR"))
        ),
	);

	$tabControl = new CAdminTabControl("tabControl", $aTabs);
	$tabControl->Begin();

	$tabControl->BeginNextTab();
	?>
	<?if ($id > 0):?>
	<tr>
		<td>ID:</td>
		<td><?= $id ?></td>
	</tr>
	<?endif;?>
	<tr>
		<td width="40%"><?=Loc::getMessage("AC_API_PROJECTS_VIEW_COLUMN_ACTIVE")?>:</td>
		<td width="60%">
            <?if(($elResult['ACTIVE'] == 'Y')) echo Loc::getMessage("AC_API_PROJECTS_VIEW_COLUMN_ACTIVE_TRUE"); else echo Loc::getMessage("AC_API_PROJECTS_VIEW_COLUMN_ACTIVE_FALSE");?>
		</td>
	</tr>
	<tr>
		<td><b><?=Loc::getMessage("AC_API_PROJECTS_VIEW_COLUMN_NAME")?></b>:</td>
		<td>
			<b><?=$elResult['NAME']?></b>
		</td>
	</tr>
	<tr>
		<td><b><?=Loc::getMessage("AC_API_PROJECTS_VIEW_COLUMN_CODE")?></b>:</td>
		<td>
			<?=$elResult['CODE']?>
		</td>
	</tr>
	<tr>
		<td><b><?=Loc::getMessage("AC_API_PROJECTS_VIEW_COLUMN_LOGIN")?></b>:</td>
		<td>
			<?=$elResult['LOGIN']?>
		</td>
	</tr>
	<tr>
		<td><b><?=Loc::getMessage("AC_API_PROJECTS_VIEW_COLUMN_PASSWORD")?></b>:</td>
		<td>
			<?=$elResult['PASSWORD']?>
		</td>
	</tr>
	<tr>
		<td><b><?=Loc::getMessage("AC_API_PROJECTS_VIEW_COLUMN_IP_ADDRESS")?></b>:</td>
		<td>
			<?=$elResult['IP_ADDRESS']?>
		</td>
	</tr>
	
	<?
	

	$tabControl->EndTab();
	$tabControl->Buttons();?>
    <input type="button" value="<?=Loc::getMessage("AC_API_PROJECTS_VIEW_BUTTON_CLOSE")?>" name="cancel" onclick="top.window.location='<?=$listUrl?>'" title="<?=Loc::getMessage("AC_API_PROJECTS_VIEW_BUTTON_CLOSE")?>">
	<?$tabControl->End();
	?>
</form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
