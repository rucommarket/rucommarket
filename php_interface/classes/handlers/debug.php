<?php
namespace Custom\Handlers;

class Debug
{
    public static function log(string $module, $data, string $method = '' , string $description = '')
    {
        $dir = $_SERVER['DOCUMENT_ROOT'].'/.logs/';
        $dateName = date('Y-m-d');
        $dirMethod = '';
        if(!empty($method)) $dirMethod = '/'.stripslashes($method);
        $dir = $dir . stripslashes($module) . $dirMethod . '/';
        CheckDirPath($dir);
        if(!empty($description)) $description .= "\r\n";
        file_put_contents($dir . $dateName.'.log' , $method."\r\n".date("d.m.Y H:i:s")."\r\n".$description.print_r($data,1)."\r\n=========================\r\n\r\n", FILE_APPEND);
    }
}