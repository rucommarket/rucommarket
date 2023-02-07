<?php
use \Bitrix\Main\ModuleManager;
use \Bitrix\Main\Config\Option;
 
Class rcm_yandex_delivery extends CModule
{
 
    var $MODULE_ID = "rcm.yandex.delivery";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;
    var $PARTNER_URI;
    var $MODULE_GROUP_RIGHTS = "N";
    var $errors = false;
 
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
        $this->MODULE_NAME = 'Интеграция с Яндекс.Доставка';
        $this->MODULE_DESCRIPTION = 'Служба доставки яндекс';
        $this->PARTNER_NAME = 'RuComMarket';
		$this->PARTNER_URI = 'https://web.rucommarket.ru/';
    }
 
    function DoInstall()
    {
        global $USER, $APPLICATION, $step;
        if (!$USER->IsAdmin()) return;

		$step = (int)$step;

        if(!ModuleManager::isModuleInstalled("sale"))
        {
            $this->errors = 'Для работы модуля необходим модуль Магазин.<br />Установите сначала модуль Магазин.';
        }
        else
        {
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();
            $arOptions = Option::getDefaults($this->MODULE_ID);
            foreach($arOptions as $option_name=>$option_value) {
                Option::set($this->MODULE_ID, $option_name, $option_value);
            }
        }
        $GLOBALS["errors"] = $this->errors;
        $APPLICATION->IncludeAdminFile('Установка модуля "Яндекс доставка"', $_SERVER['DOCUMENT_ROOT']."/local/modules/".$this->MODULE_ID."/install/step1.php");
    }
 
    function DoUninstall()
    {
        global $USER, $APPLICATION, $step;
        if (!$USER->IsAdmin()) return;

        $step = (int)$step;
		if ($step < 2)
		{
			$APPLICATION->IncludeAdminFile('Удаление модуля "Яндекс доставка"', $_SERVER['DOCUMENT_ROOT']."/local/modules/".$this->MODULE_ID."/install/unstep1.php");
		}
		elseif ($step == 2)
		{
			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));

			$this->UnInstallFiles();
            $this->UnInstallEvents();
            Option::delete($this->MODULE_ID);
            $GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile('Удаление модуля "Яндекс доставка"', $_SERVER['DOCUMENT_ROOT']."/local/modules/".$this->MODULE_ID."/install/unstep2.php");
        }
    }
 
    function InstallDB()
    {
        global $DB, $APPLICATION;
        $this->errors = false;
        if (!$DB->Query("SELECT 'x' FROM b_bitrixcloud_option WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/local/modules/".$this->MODULE_ID."/install/db/install.sql");
		}
        if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
        else
        {
            RegisterModule($this->MODULE_ID);
            return true;
        }
    }
 
    function UnInstallDB($arParams = array())
    {
        global $DB, $APPLICATION, $errors;
        $this->errors = false;
        if (!isset($arParams["savedata"]) || $arParams["savedata"] != "Y")
		{
            $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/local/modules/".$this->MODULE_ID."/install/db/uninstall.sql");
        }
        UnRegisterModule($this->MODULE_ID);
        if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
        return true;
    }
 
    function InstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler("sale","onSaleDeliveryHandlersClassNamesBuildList",$this->MODULE_ID,"\RCM\Yandex\Delivery\Handlers","addCustomDeliveryServices");
        return true;
    }
 
    function UnInstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->unregisterEventHandler("sale","onSaleDeliveryHandlersClassNamesBuildList",$this->MODULE_ID,"\RCM\Yandex\Delivery\Handlers","addCustomDeliveryServices");
        return true;
    }
 
    function InstallFiles()
    {
        if ($_ENV["COMPUTERNAME"]!='BX')
		{
            //CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/local/modules/".$this->MODULE_ID."/install/admin", $_SERVER['DOCUMENT_ROOT']."/bitrix/admin");
            //CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/tools", $_SERVER['DOCUMENT_ROOT']."/bitrix/tools", true, true);
			//CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/components", $_SERVER['DOCUMENT_ROOT']."/bitrix/components", true, true);
        }
        return true;
    }
 
    function UnInstallFiles()
    {
        if ($_ENV["COMPUTERNAME"]!='BX')
		{
            //DeleteDirFiles($_SERVER['DOCUMENT_ROOT']."/local/modules/".$this->MODULE_ID."/install/admin", $_SERVER['DOCUMENT_ROOT']."/bitrix/admin");
            //DeleteDirFilesEx("/bitrix/tools/".$this->MODULE_ID."/");
		}
		return true;
    }
}