<?php
namespace AC\Api;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use AC\Api\Internals\ProjectsTable;
use AC\Api\Internals\RequestsTable;

class Projects
{
    /**
    * Возвращает количество проектов
    *
    * @return integer
    */
    public static function getCount($arFilter = [])
    {
        if($arFilter && is_array($arFilter)) {
            $arFilter = array_diff($arFilter, array(''));
            foreach($arFilter as $key=>$val) {
				if (!is_array($val) && (strlen($val)<=0 || $val=="NOT_REF"))
					continue;

				switch(strtoupper($key))
				{
                    case "DATE_CREATE_FROM":
                        $arFilter['>=DATE_CREATE'] = FormatDateFromDB($arFilter["DATE_CREATE_FROM"],'SHORT').' 00:00:00';
                        unset($arFilter["DATE_CREATE_FROM"]);
                        break;
                    case "DATE_CREATE_TO":
                        $arFilter['<=DATE_CREATE'] = FormatDateFromDB($arFilter["DATE_CREATE_TO"],'SHORT').' 23:59:59';
                        unset($arFilter["DATE_CREATE_TO"]);
                        break;
                }
            }
        }
        $result = ProjectsTable::getCount($arFilter);
        return $result;
    }

    /**
    * Возвращает массив данных проектов из БД
    *
    * @return array
    */
    public static function getProjects($arParams = [])
    {
        if($arParams['filter'] && is_array($arParams['filter'])) {
            $arParams['filter'] = array_diff($arParams['filter'], array(''));
            foreach($arParams['filter'] as $key=>$val) {
				if (!is_array($val) && (strlen($val)<=0 || $val=="NOT_REF"))
					continue;

				switch(strtoupper($key))
				{
                    case "DATE_CREATE_FROM":
                        $arParams['filter']['>=DATE_CREATE'] = FormatDateFromDB($arParams['filter']["DATE_CREATE_FROM"],'SHORT').' 00:00:00';
                        unset($arParams['filter']["DATE_CREATE_FROM"]);
                        break;
                    case "DATE_CREATE_TO":
                        $arParams['filter']['<=DATE_CREATE'] = FormatDateFromDB($arParams['filter']["DATE_CREATE_TO"],'SHORT').' 23:59:59';
                        unset($arParams['filter']["DATE_CREATE_TO"]);
                        break;
                }
            }
        }
        $result = ProjectsTable::getList($arParams);
        if($row = $result->fetchAll()) {
            return $row;
        } else {
            return false;
        }
    }
    /**
    * проверяет наличие проекта в БД
    *
    * @return array
    */
    public static function checkProject($project = '')
    {
        if(empty($project)) return false;
        $count = self::getCount([
            'ACTIVE'=>'Y',
            'CODE'=>$project
        ]);
        if($count == 1) return true;
        return false;
    }

    /**
    * Возвращает массив данных проекта из БД по авторизации
    *
    * @return array
    */
    public static function checkAuthProject()
    {
        $context = HttpApplication::getInstance()->getContext();
        $request = $context->getRequest();
        $header = \Bitrix\Main\Config\Option::get('ac.api','HEADER');
        $auth = $request->getHeader(trim($header));
        $ip = $request->getRemoteAddress();
        $arFilter = [
            'ACTIVE' => 'Y',
            'LOGIN' => explode(':',$auth)[0],
            'PASSWORD' => explode(':',$auth)[1],
            'IP_ADDRESS' => $ip
        ];
        $count = ProjectsTable::getCount($arFilter);
        if($count>0) {
            $result = ProjectsTable::getList([
                'select' => ['ID','CODE'],
                'filter' => $arFilter,
            ]);
            return $result->Fetch();
        } else {
            return false;
        }
    }

    /**
    * Добавляет проект в БД
    *
    * @return object
    */
    public static function addProject($arFields = [])
    {
        global $USER;
        $arFields['CREATED_BY'] = $USER->getId();
        $arFields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
        $res = ProjectsTable::add($arFields);
        return $res;
    }

    /**
    * Изменяет проект в БД
    *
    * @return object
    */
    public static function updateProject($id,$arFields = [])
    {
        global $USER;
        $arFields['MODIFIED_BY'] = $USER->getId();
        $arFields['TIMESTAMP_X'] = new \Bitrix\Main\Type\DateTime();
        $res = ProjectsTable::update($id,$arFields);
        return $res;
    }

    /**
    * Удаление проекта из БД
    *
    * @return boolean
    */
    public static function deleteProject($id)
    {
        $res = ProjectsTable::delete($id);
        return $res;
    }

    /**
    * Логирование запроса
    *
    * @return boolean
    */
    public static function logProject($project = '',$request = NULL)
    {
        if(empty($project)) return false;
        $dbProject = ProjectsTable::getList([
            'select' => ['ID'],
            'filter' => ['CODE'=>$project]
        ]);
        $res = $dbProject->Fetch();
        if($res['ID'] > 0):
            RequestsTable::add([
                'PROJECT_ID' => $res['ID'],
                'REQUEST' => $request
            ]);
            return true;
        else:
            return false;
        endif;
    }
}