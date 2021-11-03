<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\HttpApplication;

Loc::loadMessages(__FILE__);

$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);
$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);
if($POST_RIGHT>="R") :
    $arGroups = [];
    $dbGroups = \Bitrix\Main\GroupTable::getList(array(
        'select'  => array('NAME','ID'),
        'filter'  => array('!ID'=>'1')
    ));
    while($arGroup = $dbGroups->Fetch()) {
        $arGroups[$arGroup['ID']] = $arGroup['NAME'];
    }

    
    \Bitrix\Main\Loader::includeModule('catalog');
    $arDiscounts = [];
    $dbDiscounts = \Bitrix\Catalog\DiscountTable::getList(array(
        'select'  => array('NAME','*'),
        'filter'  => array('!ID'=>'1','ACTIVE'=>'Y')
    ));
    while($arDiscount = $dbDiscounts->Fetch()) {
        $arDiscounts[$arDiscount['ID']] = '['.$arDiscount['ID'].']'.$arDiscount['NAME'];
    }

    \Bitrix\Main\Loader::includeModule('sale');
    $dbGroupsPrice = \Bitrix\Catalog\GroupTable::getList([
        'select' => ['ID', 'NAME'],
    ]);
    $arGroupsPrice = [];
    while($arGroup = $dbGroupsPrice->Fetch()){
        $arGroupsPrice[$arGroup['ID']] = $arGroup['NAME'];
    }

$aTabs = array(
    array(
        'DIV'     => 'edit1',
        'TAB'     => Loc::getMessage('RCM_PURCHASE_LIMIT_OPTIONS_TAB_GENERAL'),
        'TITLE'   => Loc::getMessage('RCM_PURCHASE_LIMIT_OPTIONS_TAB_GENERAL'),
        'OPTIONS' => array(
            array(
                'ACTIVE',
                Loc::getMessage('RCM_PURCHASE_LIMIT_OPTIONS_ACTIVE'),
                'N',
                array('checkbox','Y')
            ),
            array(
                'LIMIT',
                Loc::getMessage('RCM_PURCHASE_LIMIT_OPTIONS_LIMIT'),
                '20000',
                array('text', 20)
            ),
            array(
                'GROUP',
                Loc::getMessage('RCM_PURCHASE_LIMIT_OPTIONS_GROUPS'),
                '2',
                array('multiselectbox', $arGroups)
            ),
            array(
                'DISCOUNTS',
                Loc::getMessage('RCM_PURCHASE_LIMIT_OPTIONS_DISCOUNTS'),
                '',
                array('multiselectbox', $arDiscounts)
            ),
            array(
                'GROUPS_PRICE',
                Loc::getMessage('RCM_PURCHASE_LIMIT_OPTIONS_PRICES'),
                '',
                array('multiselectbox', $arGroupsPrice)
            )
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
           value="<?= Loc::GetMessage('RCM_PURCHASE_LIMIT_OPTIONS_INPUT_UPDATE'); ?>" class="adm-btn-save" />
    <input <?if ($POST_RIGHT<"W") echo "disabled" ?> type="submit" name="Default"
           value="<?= Loc::GetMessage('RCM_PURCHASE_LIMIT_OPTIONS_INPUT_DEFAULT'); ?>" />
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