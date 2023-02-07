<?php
namespace Custom\PublicSection;

use \Bitrix\Main\Application;

class WebP
{

    /**
     * метод конвертации изображения в формат webp
     * 
     * @param string $source путь к оригинальному файлу в корне сайта
     * @param string $destination путь для создания файла в корне сайта
     * 
     * @return boolean результат выполнения
     */
    private static function webpResize(string $source, string $destination) :bool
    {
        if(!file_exists(Application::getDocumentRoot().$source)) return false;

        $info = getimagesize(Application::getDocumentRoot().$source);
        $isAlpha = false;

        if ($info['mime'] == 'image/jpeg')
            $image = imagecreatefromjpeg(Application::getDocumentRoot().$source);
        elseif ($isAlpha = $info['mime'] == 'image/gif') {
            $image = imagecreatefromgif(Application::getDocumentRoot().$source);
        } elseif ($isAlpha = $info['mime'] == 'image/png') {
            $image = imagecreatefrompng(Application::getDocumentRoot().$source);
        } else {
            return false;
        }

        if ($isAlpha) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }
        imagewebp($image, Application::getDocumentRoot().$destination, 100);

        return true;
    }

    /**
     * метод формирования html для вывода
     * 
     * @param array $params параметры для формирования тега picture
     * 
     * @return string|null
     */
    public static function webpImage(array $params) :string
    {
        if(!isset($params['path']) || empty($params['path'])) return NULL;
        $source = $params['path'];
        if(!file_exists(Application::getDocumentRoot().$source)) return NULL;

        $dir = pathinfo($source, PATHINFO_DIRNAME);
        $name = pathinfo($source, PATHINFO_FILENAME);
        $destination = $dir . DIRECTORY_SEPARATOR . $name . '.webp';

        if(!file_exists(Application::getDocumentRoot().$destination) || $_GET['clear_cache'] == 'Y') {
            $sResult = self::webpResize($source,$destination);
        } else {
            $sResult = true;
        }

        $mSource = ($params['mobile'])?:null;
        $mobile = (!empty($mSource) && file_exists(Application::getDocumentRoot().$mSource));

        if($mobile) {
            $dir_m = pathinfo($mSource, PATHINFO_DIRNAME);
            $name_m= pathinfo($mSource, PATHINFO_FILENAME);
            $destination_m = $dir_m . DIRECTORY_SEPARATOR . $name_m . '.webp';

            if(!file_exists(Application::getDocumentRoot().$destination_m) || $_GET['clear_cache'] == 'Y') {
                $mResult = self::webpResize($mSource,$destination_m);
            } else {
                $mResult = true;
            }
        }

        $html = '<picture>';
        if(isset($params['class']) && !empty($params['class']))
            $html =  '<picture class="'.$params['class'].'">';
        if($mobile && $mResult)
            $html .= '<source type="image/webp" srcset="'.$destination_m.'" media="(max-width: 767.98px)">';
        if($mobile)
            $html .= '<source srcset="'.$mSource.'" media="(max-width: 767.98px)">';
        if($sResult)
            $html .= '<source type="image/webp" srcset="'.$destination.'">';
        $alt = ($params['alt'])?:'';
        if(isset($params['class_img']) && !empty($params['class_img']))
            $html .= '<img loading="lazy" src="'.$source.'" alt="'.$alt.'" class="'.$params['class_img'].'">';
        else
            $html .= '<img loading="lazy" src="'.$source.'" alt="'.$alt.'">';
        $html .= '</picture>';

        return $html;
    }

    /**
     * метод вывода html
     * 
     * @param array $params параметры для формирования тега picture
     * 
     * @return string|null
     */
    public static function webpView (array $params)
    {
        echo self::webpImage($params);
    }
}