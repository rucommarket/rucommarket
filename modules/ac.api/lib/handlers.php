<?php
namespace AC\Api;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;

class Handlers
{
    /**
     * возвращает error 403 и открывает страницу из настроек модуля
     *
     * @return void
     */
    private static function require403()
    {
        /** @global \CMain $APPLICATION */
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        //получаем страницу 403 из настроек модуля
        $page403 = \Bitrix\Main\Config\Option::get('ac.api','PAGE403');
        //открываем страницу с ошибкой 403
        require(HttpApplication::getDocumentRoot().$page403);
        die();
    }

    /**
     * проверяем блокировку на пути запроса с учетом метода
     */
    public static function onPageStart403()
    {
        //получаем переменную AC_API, если true, то игнорируем запрет доступа
        $acApi = defined('AC_API');
        $context = HttpApplication::getInstance()->getContext();
        $request = $context->getRequest();
        //получаем из настроек модуля на какой сайт (домен) действует запрет доступа
        $site_id = \Bitrix\Main\Config\Option::get('ac.api','SITE_ID');
        //пропускаем запрос если сайт из настроек модуля, и не админка, и доступ не разрешен через глобальную переменную, то только POST
        if(!$request->isPost()
            && $context->getSite() == $site_id
            && !$request->isAdminSection()
            && !$acApi
            ) self::require403();
    }

    /**
     * проверяем блокировку на пути запроса
     */
    public static function onEpilog403()
    {
        //получаем переменную AC_API, если true, то игнорируем запрет доступа
        $acApi = defined('AC_API');
        $context = HttpApplication::getInstance()->getContext();
        //получаем из настроек модуля на какой сайт (домен) действует запрет доступа
        $site_id = \Bitrix\Main\Config\Option::get('ac.api','SITE_ID');
        //пропускаем запрос если сайт из настроек модуля и доступ не разрешен через глобальную переменную
        if($context->getSite() == $site_id && !$acApi) self::require403();
    }
}