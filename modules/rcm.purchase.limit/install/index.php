<?
 
use Bitrix\Main\Localization\Loc;
use \Bitrix\Main\ModuleManager;
use \Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);
 
Class rcm_purchase_limit extends CModule
{
 
    var $MODULE_ID = "rcm.purchase.limit";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;
    var $PARTNER_URI;
    var $errors;
 
    function __construct()
    {
        $arModuleVersion = array();
        $path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path."/version.php");
        if (is_array($arModuleVersion) && isset($arModuleVersion["VERSION"]))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
        $this->MODULE_NAME = Loc::getMessage("RCM_PURCHASE_LIMIT_INSTALL_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("RCM_PURCHASE_LIMIT_INSTALL_DESC");
        $this->PARTNER_NAME = Loc::getMessage("RCM_PARTNER");
		$this->PARTNER_URI = Loc::getMessage("RCM_PARTNER_URI");
    }
 
    function DoInstall()
    {
        global $APPLICATION, $step, $errors;

		$step = (int)$step;
        $errors = false;
        
        if(!ModuleManager::isModuleInstalled("catalog")) {
            $errors = Loc::getMessage("RCM_PURCHASE_LIMIT_UNINS_CATALOG");
        } 
        elseif(!ModuleManager::isModuleInstalled("sale")) {
            $errors = Loc::getMessage("RCM_PURCHASE_LIMIT_UNINS_SALE");
        }
        else {
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();
            $arOptions = Option::getDefaults($this->MODULE_ID);
            foreach($arOptions as $option_name=>$option_value) {
                Option::set($this->MODULE_ID, $option_name, $option_value);
            }
        }

        $APPLICATION->IncludeAdminFile(Loc::getMessage("RCM_PURCHASE_LIMIT_INSTALL_TITLE"), $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/step1.php");
    }
 
    function DoUninstall()
    {
        global $APPLICATION, $step, $errors;
        $step = (int)$step;
		if ($step < 2)
		{
			$APPLICATION->IncludeAdminFile(Loc::getMessage("RCM_PURCHASE_LIMIT_INSTALL_TITLE"), $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/unstep1.php");
		}
		elseif ($step == 2)
		{
			$errors = false;

			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));

			$this->UnInstallFiles();
            $this->UnInstallEvents();
            Option::delete($this->MODULE_ID);

			$APPLICATION->IncludeAdminFile(Loc::getMessage("RCM_PURCHASE_LIMIT_INSTALL_TITLE"), $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/unstep2.php");
        }
    }
 
    function InstallDB()
    {
        global $DB;
        $this->errors = false;
        $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/db/install.sql");
        if (!$this->errors) {
            ModuleManager::registerModule($this->MODULE_ID);
            return true;
        } else
            return $this->errors;
    }
 
    function UnInstallDB($arParams = array())
    {
        global $DB, $errors;
        $errors = false;
        if (!isset($arParams["savedata"]) || $arParams["savedata"] != "Y")
		{
            $errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/db/uninstall.sql");
			if (!empty($errors))
			{
				$APPLICATION->ThrowException(implode("", $errors));
				return false;
            }
        }
        ModuleManager::unRegisterModule($this->MODULE_ID);
        return true;
    }
 
    function InstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler("catalog","OnGetDiscountResult","rcm.purchase.limit","\RCM\Purchase\Limit\Handlers","OnGetDiscountResult");
        $eventManager->registerEventHandler("catalog","OnGetOptimalPrice","rcm.purchase.limit","\RCM\Purchase\Limit\Handlers","OnGetOptimalPrice");
        return true;
    }
 
    function UnInstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        //$eventManager->unregisterEventHandler("sale","OnSaleBasketBeforeSaved","rcm.purchase.limit","\RCM\Purchase\Limit\Handlers","OnSaleBasketBeforeSaved");
        $eventManager->unregisterEventHandler("catalog","OnGetDiscountResult","rcm.purchase.limit","\RCM\Purchase\Limit\Handlers","OnGetDiscountResult");
        $eventManager->unregisterEventHandler("catalog","OnGetOptimalPrice","rcm.purchase.limit","\RCM\Purchase\Limit\Handlers","OnGetOptimalPrice");
        return true;
    }
 
    function InstallFiles()
    {
        if ($_ENV["COMPUTERNAME"]!='BX')
		{
            CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/admin", $_SERVER['DOCUMENT_ROOT']."/bitrix/admin");
        }
        return true;
    }
 
    function UnInstallFiles()
    {
        if ($_ENV["COMPUTERNAME"]!='BX')
		{
            DeleteDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/admin", $_SERVER['DOCUMENT_ROOT']."/bitrix/admin");
		}
		return true;
    }
}