<?php
namespace RCM\Yandex\Delivery;

use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Delivery\Services\Base;

class Handlers
{
    const MODULE_ID = "rcm.yandex.delivery";

    public static function addCustomDeliveryServices(/*\Bitrix\Main\Event $event*/)
    {
        $result = new \Bitrix\Main\EventResult(
            \Bitrix\Main\EventResult::SUCCESS, 
            array(
                '\RCM\Yandex\Delivery\DeliveryHandler' => '/local/modules/'.self::MODULE_ID.'/lib/DeliveryHandler.php'
            )
        );
    
        return $result;
    }
}