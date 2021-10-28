<?php
namespace Custom\PublicSection;

class Jsinit
{
    public function addJsLibrary()
    {
        $arJsConfig = array( 
            'owl_carousel' => array( 
                'js' => '/local/js/owl_carousel/owl.carousel.js',
                'css' => '/local/js/owl_carousel/assets/owl.carousel.css',
                'rel' => array('jquery'), 
            ),
        );
        foreach ($arJsConfig as $ext => $arExt) { 
            \CJSCore::RegisterExt($ext, $arExt); 
        }
    }
}