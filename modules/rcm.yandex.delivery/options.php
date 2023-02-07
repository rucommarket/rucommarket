<?

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\HttpApplication;
use \Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);
$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);
if($POST_RIGHT>="R"):
    $aTabs = array(
        array(
            'DIV'     => 'setting_api',
            'TAB'     => 'Настройки API',
            'TITLE'   => 'Настройки API',
            'OPTIONS' => [
                [
                    'URL',
                    'URL',
                    '',
                    array('text', 70)
                ],
                [
                    'TOKEN',
                    'Bearer Token',
                    '',
                    array('text', 70)
                ],
                [
                    'PLATFORM_ID',
                    'ID станции',
                    '',
                    array('text', 70)
                ]
            ]
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
               value="Обновить" class="adm-btn-save" />
        <input <?if ($POST_RIGHT<"W") echo "disabled" ?> type="submit" name="Default"
               value="Сбросить" />
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