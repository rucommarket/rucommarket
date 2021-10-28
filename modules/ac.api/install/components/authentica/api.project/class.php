<?php
use \Bitrix\Main\Loader;
use \AC\Api\Projects;
use \Bitrix\Main\HttpApplication;

class AuthenticaApiProjects extends CBitrixComponent
{
    protected $componentPage = '';
    protected $arUrl = [];

    private function complex()
    {
        $arDefaultUrlTemplates404 = array(
            "nomethod" => "",
            "method" => "#METHOD#"
        );
        $arDefaultVariableAliases404 = array();
        $arDefaultVariableAliases = array();
        $arComponentVariables = array(
            "METHOD"
        );
        if($this->arParams["SEF_MODE"] == "Y")
        {
            $arVariables = array();
        
            $engine = new CComponentEngine($this);
            $engine->addGreedyPart("#METHOD#");
            $engine->setResolveCallback(array("CIBlockFindTools", "resolveComponentEngine"));
            $arUrlTemplates = CComponentEngine::makeComponentUrlTemplates($arDefaultUrlTemplates404, $this->arParams["SEF_URL_TEMPLATES"]);
            $arVariableAliases = CComponentEngine::makeComponentVariableAliases($arDefaultVariableAliases404, $this->arParams["VARIABLE_ALIASES"]);
            $this->componentPage = $engine->guessComponentPath(
                $this->arParams["SEF_FOLDER"],
                $arUrlTemplates,
                $arVariables
            );
            CComponentEngine::initComponentVariables($this->componentPage, $arComponentVariables, $arVariableAliases, $arVariables);
            $this->arUrl = $arVariables;
        }
        else
        {
            echo "<b>Компонент работает не правильно! Включите ЧПУ!</b>";
            require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_before.php");
            die();
        }
    }

    private function requestExistence($projectCode = false)
    {
        if(is_array($this->arUrl) && !empty($projectCode) && !empty($this->arUrl['METHOD'])):
            $arResult = &$this->arResult;
            //проверяем сучществование проекта
            if(Projects::checkProject($projectCode)) {
                $arRequest= $_REQUEST;
                $method = $this->arUrl['METHOD'];
                $classApi = '\AC\\'.ucfirst($projectCode).'\Api';
                //проверяем существование и подключение модуля проекта, а также существование в модуле класса API
                if(Loader::includeModule('ac.'.$projectCode) && class_exists($classApi)) {
                    //логируем запрос от проекта
                    Projects::logProject($projectCode,json_encode($arRequest));
                    $obProject = new $classApi();
                    //проверяем существование метода, в ответ отдаем результат обработки или ошибку
                    if(!method_exists($obProject,$method)) {
                        $error = \AC\Api\Error::getError(1);
                        $arResult['ERROR_CODE'] = $error['ERROR_CODE'];
                        $arResult['ERROR_TEXT'] = $error['ERROR_TEXT'];
                    } else {
                        $arResult = $obProject::$method($arRequest);
                    }
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        else:
            return false;
        endif;
    }

    public function executeComponent()
    {
        $this->complex();
        
        $request = HttpApplication::getInstance()->getContext()->getRequest();

        if($auth = Projects::checkAuthProject())
            $existence = $this->requestExistence($auth['CODE']);

        if($request->isPost() && $auth && $existence) define('AC_API',true);

        
        $this->IncludeComponentTemplate();
    }
}