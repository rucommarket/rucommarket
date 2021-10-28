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
if ($id < 0)
	$id = 0;

ClearVars();

$errorMessage = '';
$bVarsFromForm = false;

$userId = (int)$USER->GetID();

$entityId = "Project";


if ($_SERVER["REQUEST_METHOD"] == "POST" && strlen($request->getPost("Update")) > 0 && !$bReadOnly && check_bitrix_sessid())
{
	if (trim($NAME) == '')
		$errorMessage .= Loc::getMessage("AC_API_PROJECTS_EDIT_ERROR_FIELD_NAME")."<br>";
	if (trim($CODE) == '')
		$errorMessage .= Loc::getMessage("AC_API_PROJECTS_EDIT_ERROR_FIELD_CODE")."<br>";
	if (trim($LOGIN) == '')
		$errorMessage .= Loc::getMessage("AC_API_PROJECTS_EDIT_ERROR_FIELD_LOGIN")."<br>";
	if (trim($PASSWORD) == '')
		$errorMessage .= Loc::getMessage("AC_API_PROJECTS_EDIT_ERROR_FIELD_PASSWORD")."<br>";
	if (trim($IP_ADDRESS) == '')
		$errorMessage .= Loc::getMessage("AC_API_PROJECTS_EDIT_ERROR_FIELD_IP_ADDRESS")."<br>";

	$arFields = [
        'ACTIVE' => ($request->get('ACTIVE')) ? trim($request->get('ACTIVE')) : 'N',
        'NAME' => ($request->get('NAME')) ? trim($request->get('NAME')) : '',
        'CODE' => ($request->get('CODE')) ? trim($request->get('CODE')) : '',
		'LOGIN' => ($request->get('LOGIN')) ? trim($request->get('LOGIN')) : '',
        'PASSWORD' => ($request->get('PASSWORD')) ? trim($request->get('PASSWORD')) : '',
        'IP_ADDRESS' => ($request->get('IP_ADDRESS')) ? trim($request->get('IP_ADDRESS')) : '',
	];

	$USER_FIELD_MANAGER->EditFormAddFields($entityId, $arFields);

	$DB->StartTransaction();

	if ($errorMessage == '')
	{
		if ($id > 0)
		{
			$res = \AC\Api\Projects::updateProject($id, $arFields);
			if(!$res->isSuccess() && $arErrors = $res->getErrorMessages()) {
				foreach($arErrors as $err) {
					$errorMessage .= $err.'<br>';
				}
			}
		}
		else
		{
			$res = \AC\Api\Projects::addProject($arFields);
			if(!$res->isSuccess() && $arErrors = $res->getErrorMessages()) {
				foreach($arErrors as $err) {
					$errorMessage .= $err.'<br>';
				}
			} else {
				$id = $res->getId();
			}
		}
		if ($id > 0)
			$viewUrl = $selfFolderUrl."ac_api_projects_view.php?ID=".$id."&lang=".LANGUAGE_ID;
		if (!$res)
		{
			if ($ex = $APPLICATION->GetException())
				$errorMessage .= $ex->GetString()."<br>";
			else
				$errorMessage .= Loc::getMessage('AC_API_PROJECTS_EDIT_ERROR_SAVE').'<br>';
		}
		else
		{
			$ufUpdated = $USER_FIELD_MANAGER->Update($entityId, $id, $arFields);
		}
	}

	if ($errorMessage == '')
	{
		$DB->Commit();

		if (strlen($_REQUEST["apply"]) <= 0)
		{
			LocalRedirect($listUrl);
		}
		else
		{
			$applyUrl = $selfFolderUrl."ac_api_projects_edit.php?ID=".$id."&lang=".LANGUAGE_ID;
			LocalRedirect($applyUrl);
		}
	}
	else
	{
		$bVarsFromForm = true;
		$DB->Rollback();
	}
	$bVarsFromForm = true;
	$DB->Rollback();
}

if ($id > 0)
	$APPLICATION->SetTitle(str_replace("#ID#", $id, Loc::getMessage("AC_API_PROJECTS_EDIT_TITLE_UPDATE")));
else
	$APPLICATION->SetTitle(Loc::getMessage("AC_API_PROJECTS_EDIT_TITLE_ADD"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
$elResult['ACTIVE'] = "Y";

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
} else {
	$elResult = $_REQUEST;
}

$aMenu = array(
	array(
		"TEXT" => Loc::getMessage("AC_API_PROJECTS_EDIT_LIST"),
		"ICON" => "btn_list",
		"LINK" => $listUrl
	)
);
$context = new CAdminContextMenu($aMenu);
$context->Show();

$arSitesShop = array();
$arSitesTmp = array();
$rsSites = CSite::GetList($_REQUEST["by"] = "id", $_REQUEST["order"] = "asc", Array("ACTIVE" => "Y"));
while($arSite = $rsSites->GetNext())
{
	$site = COption::GetOptionString("sale", "SHOP_SITE_".$arSite["ID"], "");
	if ($arSite["ID"] == $site)
	{
		$arSitesShop[] = array("ID" => $arSite["ID"], "NAME" => $arSite["NAME"]);
	}
	$arSitesTmp[] = array("ID" => $arSite["ID"], "NAME" => $arSite["NAME"]);
}

$rsCount = count($arSitesShop);
if ($rsCount <= 0)
{
	$arSitesShop = $arSitesTmp;
	$rsCount = count($arSitesShop);
}

CAdminMessage::ShowMessage($errorMessage);

$actionUrl = $APPLICATION->GetCurPage();

?>
<form enctype="multipart/form-data" method="POST" action="<?=$actionUrl?>" name="ac_api_projects_edit">
	<?echo GetFilterHiddens("filter_");?>
    <input type="hidden" name="Update" value="Y">
	<input type="hidden" name="lang" value="<?echo LANGUAGE_ID; ?>">
	<input type="hidden" name="ID" value="<?echo $id ?>">
	<?=bitrix_sessid_post()?><?
	$aTabs = array(
		array("DIV" => "edit1", "TAB" => Loc::getMessage("AC_API_PROJECTS_EDIT_TAB"), "ICON" => "catalog", "TITLE" => Loc::getMessage("AC_API_PROJECTS_EDIT_TAB_DESCR")),
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
		<td width="40%"><?=Loc::getMessage("AC_API_PROJECTS_EDIT_COLUMN_ACTIVE")?>:</td>
		<td width="60%">
			<input type="checkbox" name="ACTIVE" value="Y" <?if(($elResult['ACTIVE'] == 'Y') || ($id == 0)) echo "checked";?> size="50" />
		</td>
	</tr>
	<tr>
		<td><b><?=Loc::getMessage("AC_API_PROJECTS_EDIT_COLUMN_NAME")?></b>:</td>
		<td>
			<input type="text" style="width:300px" name="NAME" value="<?=$elResult['NAME']?>"/>
		</td>
	</tr>
	<tr>
		<td><b><?=Loc::getMessage("AC_API_PROJECTS_EDIT_COLUMN_CODE")?></b>:</td>
		<td>
			<input type="text" style="width:300px" name="CODE" id="CODE" value="<?=$elResult['CODE']?>"/>
		</td>
	</tr>
	<tr>
		<td><b><?=Loc::getMessage("AC_API_PROJECTS_EDIT_COLUMN_LOGIN")?></b>:</td>
		<td>
			<input type="text" style="width:300px" name="LOGIN" id="LOGIN" value="<?=$elResult['LOGIN']?>"/>
		</td>
	</tr>
	<tr>
		<td><b><?=Loc::getMessage("AC_API_PROJECTS_EDIT_COLUMN_PASSWORD")?></b>:</td>
		<td>
			<input type="text" style="width:300px" name="PASSWORD" id="PASSWORD" value="<?=$elResult['PASSWORD']?>"/>
		</td>
	</tr>
	<tr>
		<td><b><?=Loc::getMessage("AC_API_PROJECTS_EDIT_COLUMN_IP_ADDRESS")?></b>:</td>
		<td>
			<input type="text" style="width:300px" name="IP_ADDRESS" id="IP_ADDRESS" value="<?=$elResult['IP_ADDRESS']?>"/>
		</td>
	</tr>
	
	<?
	$arUserFields = $USER_FIELD_MANAGER->GetUserFields($entityId, $id, LANGUAGE_ID);
	foreach($arUserFields as $FIELD_NAME => $arUserField)
	{
		$arUserField["VALUE_ID"] = intval($id);
		$strLabel = $arUserField["EDIT_FORM_LABEL"]? $arUserField["EDIT_FORM_LABEL"]: $arUserField["FIELD_NAME"];
		$arUserField["EDIT_FORM_LABEL"] = $strLabel;

		echo $USER_FIELD_MANAGER->GetEditFormHTML($bVarsFromForm, $GLOBALS[$FIELD_NAME], $arUserField);

		$form_value = $GLOBALS[$FIELD_NAME];
		if(!$bVarsFromForm)
			$form_value = $arUserField["VALUE"];
		elseif($arUserField["USER_TYPE"]["BASE_TYPE"]=="file")
			$form_value = $GLOBALS[$arUserField["FIELD_NAME"]."_old_id"];
	}

	$tabControl->EndTab();
	$tabControl->Buttons(array("disabled" => $bReadOnly, "back_url" => $listUrl));
	$tabControl->End();
	?>
</form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
