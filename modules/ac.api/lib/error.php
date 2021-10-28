<?php
namespace AC\Api;

use Bitrix\Main\Localization\Loc;

class Error
{
    //по коду ошибки передается текст ошибки из языкового файла
    public function getError($code)
    {
        $result = [
            'ERROR_CODE' => $code,
            'ERROR_TEXT' => Loc::getMessage('AC_API_ERROR_'.$code)
        ];
        return $result;
    }
}