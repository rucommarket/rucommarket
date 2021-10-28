<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\HttpApplication;

Loc::loadMessages(__FILE__);

$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);
$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);
$dbSites = CSite::GetList($by="sort", $order="desc",[]);
while($site = $dbSites->Fetch()):
    $arSites[$site['LID']] = $site['NAME'];
endwhile;

if($POST_RIGHT>="R") :

    $aTabs = array(
        array(
            'DIV'     => 'edit1',
            'TAB'     => Loc::getMessage('AC_API_OPTIONS_TAB_GENERAL'),
            'TITLE'   => Loc::getMessage('AC_API_OPTIONS_TAB_GENERAL'),
            'OPTIONS' => array(
                array(
                    'SITE_ID',
                    Loc::getMessage('AC_API_OPTIONS_SITE_ID'),
                    's1',
                    array('selectbox', $arSites)
                ),
                array(
                    'HEADER',
                    Loc::getMessage('AC_API_OPTIONS_HEADER'),
                    'AC-API-AUTH',
                    array('text', 10)
                ),
                array(
                    'PAGE403',
                    Loc::getMessage('AC_API_OPTIONS_403'),
                    '/403.php',
                    array('text', 40)
                ),
            )
        ),
        array(
            "DIV" => "group_rights",
            "TAB" => GetMessage("MAIN_TAB_RIGHTS"),
            "ICON" => "support_settings",
            "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")
        ),
    );
    $tabControl = new CAdminTabControl(
        'tabControl',
        $aTabs
    );
    
    
    $tabControl->begin();
    ?>
    <form action="<?=$APPLICATION->getCurPage();?>?mid=<?=$module_id; ?>&lang=<?=LANGUAGE_ID; ?>" method="post">
        <?php
        foreach ($aTabs as $aTab) {
            if ($aTab['OPTIONS']) {
                $tabControl->beginNextTab();
                __AdmSettingsDrawList($module_id, $aTab['OPTIONS']);
            } elseif ($aTab['DIV'] == 'group_rights') {
                $tabControl->beginNextTab();
                require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
            }
        }?>
    <?$tabControl->buttons();?>
        <input <?if ($POST_RIGHT<"W") echo "disabled" ?> type="submit" name="Update" 
               value="<?= Loc::GetMessage('AC_API_OPTIONS_INPUT_UPDATE'); ?>" class="adm-btn-save" />
        <input <?if ($POST_RIGHT<"W") echo "disabled" ?> type="submit" name="Default"
               value="<?= Loc::GetMessage('AC_API_OPTIONS_INPUT_DEFAULT'); ?>" />
        <?=bitrix_sessid_post(); ?>
    <?$tabControl->end();?>
    </form>
    <?
    
    if ($request->isPost() && check_bitrix_sessid() && $POST_RIGHT == "W" & (strlen($Update)>0 || strlen($Default)>0)) {
    
        foreach ($aTabs as $aTab) {
            foreach ($aTab['OPTIONS'] as $arOption) {
                if (!is_array($arOption)) {
                    continue;
                }
                if ($arOption['note']) {
                    continue;
                }
                if ($request->getPost('Update')) {
    
                    $optionValue = $request->getPost($arOption[0]);
                    Option::set($module_id, $arOption[0], (is_array($optionValue)) ? implode(',', $optionValue) : $optionValue);
    
                } elseif ($request->getPost('Default')) { // устанавливаем по умолчанию
                    Option::set($module_id, $arOption[0], $arOption[2]);
                    $gr = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
                    while($g = $gr->Fetch())
                    {
                        $APPLICATION->DelGroupRight($module_id, array($g["ID"]));
                    }
                }
            }
            if ($aTab['DIV'] == 'group_rights') {
                ob_start();
                require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
                ob_end_clean();
            }
        }
    
        LocalRedirect($APPLICATION->getCurPage().'?mid='.$module_id.'&lang='.LANGUAGE_ID);
    
    }
    
    endif;
    ?>