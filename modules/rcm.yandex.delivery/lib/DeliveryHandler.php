<?php
namespace RCM\Yandex\Delivery;

use Bitrix\Sale;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Delivery\Services\Base;
use RCM\Yandex\Delivery\Connect;
use \Bitrix\Main\Data\Cache;
use \Bitrix\Main\Type\DateTime;

class DeliveryHandler extends Base
{
    public function __construct(array $initParams)
    {
        parent::__construct($initParams);
    }

    public static function getClassTitle()
    {
        return 'Служба доставки Яндекс';
    }
    
    public static function getClassDescription()
    {
        return 'Служба доставки Яндекс';
    }

    static function getPropertyByCode($propertyCollection, $code)  
	{
		    foreach ($propertyCollection as $property)
		    {
		        if($property->getField('CODE') == $code)
		            return $property;
		    }
	}

    private function __calculateServiceType()
    {
        return [];
    }
    
    protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment)
    {
        global $USER;
        if(!is_object($USER)) $USER = new \CUser();

        $result = new CalculationResult();

        $weight = number_format((floatval($shipment->getWeight()) / 1000), 2);
        if($weight <= 0) {
            $result->addError(new \Bitrix\Main\Error("Вес не может быть равен 0"));
            return $result;
        }

        $order = $shipment->getOrder(); // заказ
        $props = $order->getPropertyCollection();
        $cityTo = self::getPropertyByCode($props, "CITY_TO");
        $regionTo = self::getPropertyByCode($props, "REGION_TO");
        $addressTo = self::getPropertyByCode($props, "ADDRESS_STRING");

        $region = $regionTo->getValue();
        $city = $cityTo->getValue();

        $connect = new Connect();

        if(!isset($city) || empty($city)) {
            $result->addError(new \Bitrix\Main\Error("Не указан город"));
            return $result;
        }
        //определяем при каких расчетах выводить доставку
        $return = false;
        $variants = [];
        //определяем стоимость доставки курьером по мск и регионам
        if($this->config["MAIN"]["POSTING_TYPE"] == "Courier") {
            $calc = $connect->pricingCalculatorAddress($addressTo->getValue(),(int)$weight,(int)($order->getPrice()));
            if(isset($calc['pricing_total']))
                $price = $calc['pricing_total'];
            
            if($region == 'Москва') {
                if($this->config["MAIN"]["FREE_PRICE_MOSCOW"] > 0 && $order->getPrice() >= $this->config["MAIN"]["FREE_PRICE_MOSCOW"] && \CSite::InGroup([45]) == false) {
                    $price = 0;
                }
                $deliveryData['DELIVERY_TEXT'] = 'до 3 рабочих дней';
            } else {
                if($this->config["MAIN"]["FREE_PRICE_REGION"] > 0 && $order->getPrice() >= $this->config["MAIN"]["FREE_PRICE_REGION"] && \CSite::InGroup([45]) == false) {
                    $price = 0;
                }
                $deliveryData['DELIVERY_TEXT'] = 'до 4 рабочих дней';
            }
            $return = true;
            $mult = 0;
        } elseif($this->config["MAIN"]["POSTING_TYPE"] == "PickPoint") {
            $cache = Cache::createInstance();
            if($cache->initCache(7200, "address_".Sale\Fuser::getId(),'addresses'))
                $address = $cache->getVars();
            if(isset($address['LAT']) && isset($address['LON'])) {
                $arVariants = $connect->getPosts(floatval($address['LAT']),floatval($address['LON']));
                if(empty($arVariants) || isset($arVariants['error_details'])) {
                    $result->addError(new \Bitrix\Main\Error("Доставка не рассчитана для указанных параметров"));
                } else {
                    if($city == 'Москва') {
                        if($this->config["MAIN"]["FREE_PRICE_MOSCOW"] > 0 && $order->getPrice() >= $this->config["MAIN"]["FREE_PRICE_MOSCOW"] && \CSite::InGroup([45]) == false) {
                            $price = 0;
                        } else {
                            $calc = $connect->pricingCalculatorPickpoint((string)$arVariants['points'][0]['id'],(int)$weight,(int)($order->getPrice()));
                            $price = $calc['pricing_total'];
                        }
                        $deliveryData['DELIVERY_TEXT'] = 'до 3 рабочих дней';
                    } else {
                        if($this->config["MAIN"]["FREE_PRICE_REGION"] > 0 && $order->getPrice() >= $this->config["MAIN"]["FREE_PRICE_REGION"] && \CSite::InGroup([45]) == false) {
                            $price = 0;
                        } else {
                            $calc = $connect->pricingCalculatorPickpoint((string)$arVariants['points'][0]['id'],(int)$weight,(int)($order->getPrice()));
                            $price = $calc['pricing_total'];
                        }
                        $deliveryData['DELIVERY_TEXT'] = 'до 4 рабочих дней';
                    }
                    $return = true;
                    $mult = 1;
                    foreach($arVariants['points'] as $key=>$variant) {
                        $variants[$key]['Address'] = $variant['address']['full_address'];
                        $variants[$key]['Id'] = $variant['id'];
                        $variants[$key]['Phone'] = $variant['contact']['phone'];
                        $variants[$key]['Lat'] = $variant['position']['latitude'];
                        $variants[$key]['Long'] = $variant['position']['longitude'];
                        $variants[$key]['Name'] = $variant['name'];
                    }
                }
            }
        }

        if($return && isset($price)) {
            $result->setDeliveryPrice($price);
            $result->setTmpData([
                "DELIVERY_TEXT" => $deliveryData['DELIVERY_TEXT'],
                "DELIVERY_DATES" => $deliveryData['DELIVERY_DATES'],
                "DELIVERY_VARIANTS" => $variants ?: [],
                "MULTIVARIANT" => $mult,
                "PERIOD" => 1
            ]);
        } else {
            $result->addError(new \Bitrix\Main\Error("Доставка не рассчитана для указанных параметров"));  
        }
        //$result->addError(new \Bitrix\Main\Error("Проводится тестирование"));
        return $result;
    }
    
    public function isCalculatePriceImmediately()
    {
        return true;
    }
    
    public static function whetherAdminExtraServicesShow()
    {
        return true;
    }

    protected function getConfigStructure()
    {
        $result = array(
            'MAIN' => array(
                'TITLE' => 'Основные',
                'DESCRIPTION' => 'Основные настройки',
                'ITEMS' => array(
                    'POSTING_TYPE' => array(
                        'TYPE' => 'ENUM',
                        'NAME' => 'Тип отправки',
                        'DEFAULT' => 'Courier',
                        'OPTIONS' => array(
                                'Courier' => 'Курьер',
                                'PickPoint' => 'Пункт выдачи',
                                'Postamat' => 'Постамат'
                        )
                    ),
                    'FREE_PRICE_MOSCOW' => array(
                        'TYPE' => 'NUMBER',
                        'NAME' => 'Минимальная сумма для бесплатной доставка по Москве (если 0, то не учитывается)',
                    ),
                    'FREE_PRICE_REGION' => array(
                        'TYPE' => 'NUMBER',
                        'NAME' => 'Минимальная сумма для бесплатной доставка по регионам (если 0, то не учитывается)',
                    )
                )
            )
        );
        return $result;
    }
}