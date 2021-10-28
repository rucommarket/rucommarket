<?php
use \Bitrix\Main\Loader;
use \Bitrix\Main\Context;
use Bitrix\Main\EventManager;

$request = Context::getCurrent()->getRequest();
$eventManager = EventManager::getInstance();

if(file_exists(Loader::getLocal('php_interface/functions.php')))
    {require_once (Loader::getLocal('php_interface/functions.php'));}

if($request->isAdminSection()):
    Loader::registerNamespace(
        "Custom\AdminSection",
        Loader::getLocal('php_interface/classes/adminSection')
    );
    if(file_exists(Loader::getLocal('php_interface/adminHandlers.php')))
        {require_once (Loader::getLocal('php_interface/adminHandlers.php'));};
else:
    Loader::registerNamespace(
        "Custom\PublicSection",
        Loader::getLocal('php_interface/classes/public')
    );
    if(file_exists(Loader::getLocal('php_interface/publicHandlers.php')))
        {require_once (Loader::getLocal('php_interface/publicHandlers.php'));};
endif;


Loader::registerNamespace(
    "Custom\Handlers",
    Loader::getLocal('php_interface/classes/handlers')
);
if(file_exists(Loader::getLocal('php_interface/handlers.php')))
    require_once (Loader::getLocal('php_interface/handlers.php'));