<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rcm.purchase.limit/include.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */

global $APPLICATION;
global $DB;
global $USER;
global $USER_FIELD_MANAGER;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\HttpApplication;

$selfFolderUrl = '/bitrix/admin/';
$listUrl = $selfFolderUrl."rcm_purchase_limit.php?lang=".LANGUAGE_ID;
Loc::loadMessages(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("rcm.purchase.limit");
if($POST_RIGHT=="D")
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
$bReadOnly = false;
if($POST_RIGHT=="R")
	$bReadOnly = true;
$request = HttpApplication::getInstance()->getContext()->getRequest();

$id = (!empty($request->get('ID')) ? (int)$request->get('ID') : 0);
if ($id < 0)
	$id = 0;

ClearVars();

$errorMessage = '';
$bVarsFromForm = false;

$userId = (int)$USER->GetID();

$entityId = "SELECTION";

if ($_SERVER["REQUEST_METHOD"] == "POST" && strlen($request->getPost("Update")) > 0 && !$bReadOnly && check_bitrix_sessid())
{
	//$adminSidePanelHelper->decodeUriComponent();

	if (trim($USER_ID) == '')
		$errorMessage .= Loc::getMessage("rcm_purchase_limit_error_user_id")."<br>";
	if (trim($LIMIT) == '')
		$errorMessage .= Loc::getMessage("rcm_purchase_limit_error_limit")."<br>";

	$arFields = [
		'ACTIVE' => ($request->get('ACTIVE')) ? $request->get('ACTIVE') : 'N',
        'USER_ID' => ($request->get('USER_ID')) ? intval($request->get('USER_ID')) : false,
        'LIMIT' => ($request->get('LIMIT')) ? trim($request->get('LIMIT')) : '',
	];

	$USER_FIELD_MANAGER->EditFormAddFields($entityId, $arFields);

	$DB->StartTransaction();

	if ($errorMessage == '')
	{
		if ($id > 0)
		{
            $arFields['MODIFIED_BY'] = $USER->getID();
            $arFields['TIMESTAMP_X'] = new \Bitrix\Main\Type\DateTime();
			$res = \RCM\Purchase\Limit\Internals\UsersTable::update($id, $arFields);
            if(!$res->isSuccess() && $arErrors = $res->getErrorMessages()) {
				foreach($arErrors as $err) {
					$errorMessage .= $err.'<br>';
				}
			} else {
				$id = $res->getId();
			}
		}
		else
		{
            $arFields['CREATED_BY'] = $USER->getID();
            $arFields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
            $arFields['MODIFIED_BY'] = $USER->getID();
            $arFields['TIMESTAMP_X'] = new \Bitrix\Main\Type\DateTime();
			$res = \RCM\Purchase\Limit\Internals\UsersTable::add($arFields);
			if(!$res->isSuccess() && $arErrors = $res->getErrorMessages()) {
				foreach($arErrors as $err) {
					$errorMessage .= $err.'<br>';
				}
			} else {
				$id = $res->getId();
			}
		}
		if (!$res)
		{
			if ($ex = $APPLICATION->GetException())
				$errorMessage .= $ex->GetString()."<br>";
			else
				$errorMessage .= Loc::getMessage('rcm_purchase_limit_error_save').'<br>';
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
			$applyUrl = $selfFolderUrl."rcm_purchase_limit_edit.php?lang=".LANGUAGE_ID."&ID=".$id;
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
	$APPLICATION->SetTitle(str_replace("#ID#", $id, Loc::getMessage("rcm_purchase_limit_title_update")));
else
	$APPLICATION->SetTitle(Loc::getMessage("rcm_purchase_limit_title_add"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
$elResult['ACTIVE'] = "Y";

if($id > 0)
{
	$elResult = \RCM\Purchase\Limit\Internals\UsersTable::getList([
		'select' => [
			'*',
			'CREATED_BY_LOGIN'=>'CREATED_BY_USER.LOGIN',
			'CREATED_BY_NAME'=>'CREATED_BY_USER.NAME',
			'CREATED_BY_LAST_NAME'=>'CREATED_BY_USER.LAST_NAME',
			'MODIFIED_BY_LOGIN'=>'MODIFIED_BY_USER.LOGIN',
			'MODIFIED_BY_NAME'=>'MODIFIED_BY_USER.NAME',
			'MODIFIED_BY_LAST_NAME'=>'MODIFIED_BY_USER.LAST_NAME',
			'USER_LOGIN'=>'USER.LOGIN',
			'USER_NAME'=>'USER.NAME',
			'USER_LAST_NAME'=>'USER.LAST_NAME'
		],
		'filter' => ['ID' => $id],
		'limit' => 1
	]);
	$elResult = $elResult->Fetch();
}

$aMenu = array(
	array(
		"TEXT" => Loc::getMessage("rcm_purchase_limit_list"),
		"ICON" => "btn_list",
		"LINK" => $listUrl
	)
);

if ($id > 0 && !$bReadOnly)
{
	$aMenu[] = array("SEPARATOR" => "Y");

	$addUrl = $selfFolderUrl."rcm_purchase_limit_edit.php?lang=".LANGUAGE_ID;
	$aMenu[] = array(
		"TEXT" => Loc::getMessage("rcm_purchase_limit_new"),
		"ICON" => "btn_new",
		"LINK" => $addUrl
	);
	$deleteUrl = $selfFolderUrl."rcm_purchase_limit.php?action_button=delete&ID=".$id."&lang=".LANGUAGE_ID."&".bitrix_sessid_get()."#tb";

	$aMenu[] = array(
		"TEXT" => Loc::getMessage("rcm_purchase_limit_delete"),
		"ICON" => "btn_delete",
		"LINK" => "javascript:if(confirm('".Loc::getMessage("rcm_purchase_limit_delete_confirm")."')) top.window.location='".$deleteUrl."';",
		"WARNING" => "Y"
	);
}
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

$userFieldUrl = $selfFolderUrl."userfield_edit.php?lang=".LANGUAGE_ID."&ENTITY_ID=".$entityId;

$userFieldUrl .= "&back_url=".urlencode($APPLICATION->GetCurPageParam('', array('bxpublic'))."&tabControl_active_tab=user_fields_tab");
?>
<form enctype="multipart/form-data" method="POST" action="<?=$actionUrl?>" name="rcm_purchase_limit_edit">
	<?echo GetFilterHiddens("filter_");?>
	<input type="hidden" name="Update" value="Y">
	<input type="hidden" name="lang" value="<?echo LANGUAGE_ID; ?>">
	<input type="hidden" name="ID" value="<?echo $id ?>">
	<?=bitrix_sessid_post()?><?
	$aTabs = array(
		array("DIV" => "edit1", "TAB" => Loc::getMessage("rcm_purchase_limit_tab"), "ICON" => "catalog", "TITLE" => Loc::getMessage("rcm_purchase_limit_tab_descr")),
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
		<td width="40%"><?=Loc::getMessage("rcm_purchase_limit_column_active")?>:</td>
		<td width="60%">
			<input type="checkbox" name="ACTIVE" value="Y" <?if(($elResult['ACTIVE'] == 'Y') || ($id == 0)) echo "checked";?> size="50" />
		</td>
	</tr>
    <tr>
		<td><?=Loc::getMessage("rcm_purchase_limit_column_user")?>:</td>
		<td>
			<input type="text" style="width:165px" name="USER_ID" id="USER_ID" value="<?=$elResult['USER_ID']?>"/>&nbsp;
			<input type="button" value="<?=Loc::getMessage("rcm_purchase_limit_column_user_add")?>" onclick="window.open('/bitrix/admin/user_search.php?lang=ru&FN=rcm_purchase_limit_edit&FC=USER_ID', '', 'scrollbars=yes,resizable=yes,width=760,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));">
		</td>
	</tr>
	<tr>
		<td><b><?=Loc::getMessage("rcm_purchase_limit_column_limit")?></b>:</td>
		<td>
			<input type="text" style="width:165px" name="LIMIT" id="LIMIT" value="<?=$elResult['LIMIT']?>"/>&nbsp;
		</td>
	</tr>
	<?if ($id > 0):?>
	<tr>
		<td><?=Loc::getMessage("rcm_purchase_limit_column_date_create")?>:</td>
		<td>
			<?=$elResult['DATE_CREATE']?>
		</td>
	</tr>
	<?endif;?>
	<?if ($id > 0):?>
	<tr>
		<td><?=Loc::getMessage("rcm_purchase_limit_column_created_by")?></b>:</td>
		<td>
			<?=$elResult['CREATED_BY_NAME']?> <?=$elResult['CREATED_BY_LAST_NAME']?> <a href="user_edit.php?ID=<?=$elResult['CREATED_BY']?>&lang=<?=LANG?>"> (<?=$elResult['CREATED_BY_LOGIN']?>)</a>
		</td>
	</tr>
	<?endif;?>
	<?if ($id > 0):?>
	<tr>
		<td><?=Loc::getMessage("rcm_purchase_limit_column_timestamp")?>:</td>
		<td>
			<?=$elResult['TIMESTAMP_X']?>
		</td>
	</tr>
	<?endif;?>
	<?if ($id > 0):?>
	<tr>
		<td><?=Loc::getMessage("rcm_purchase_limit_column_modified_by")?>:</td>
		<td>
			<?=$elResult['MODIFIED_BY_NAME']?> <?=$elResult['MODIFIED_BY_LAST_NAME']?> <a href="user_edit.php?ID=<?=$elResult['MODIFIED_BY']?>&lang=<?=LANG?>"> (<?=$elResult['MODIFIED_BY_LOGIN']?>)</a>
		</td>
	</tr>
	<?endif;?>

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
