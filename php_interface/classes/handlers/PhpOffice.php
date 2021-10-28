<?php
namespace Custom\Handlers;

use \Bitrix\Main\Loader;

class PhpOffice
{
    public function init()
    {
        Loader::registerNamespace(
            "Psr\SimpleCache",
            Loader::getLocal('php_interface/classes/other/SimpleCache')
        );
        Loader::registerNamespace(
            "MyCLabs\Enum",
            Loader::getLocal('php_interface/classes/other/MyCLabs')
        );
        Loader::registerNamespace(
            "ZipStream",
            Loader::getLocal('php_interface/classes/other/ZipStream')
        );
        Loader::registerNamespace(
            "PhpOffice\PhpSpreadsheet",
            Loader::getLocal('php_interface/classes/other/PhpSpreadsheet')
        );
    }
}