<?php
namespace RCM\Yandex\Delivery;

use \Bitrix\Main\Web\HttpClient;
use \Bitrix\Main\Config\Option;
use \Bitrix\Sale;
use \Bitrix\Main\Type\DateTime;

class Connect
{
    var $URL = '';
    var $TOKEN = '';
    var $PLATFORM_ID = '';
    var $data = [];
    public function __construct()
    {
        $this->URL = Option::get('rcm.yandex.delivery','URL');
        $this->TOKEN = Option::get('rcm.yandex.delivery','TOKEN');
        $this->PLATFORM_ID = Option::get('rcm.yandex.delivery','PLATFORM_ID');
        $this->httpClient = new HttpClient();
        $this->httpClient->setHeader('Content-Type', 'application/json', true);
        $this->httpClient->setHeader('Authorization','Bearer '.$this->TOKEN,false);
    }

    public function requestPost(string $method)
    {
        $result = $this->httpClient->post(
            $this->URL.$method,
            \Bitrix\Main\Web\Json::encode($this->data),
            false
        );
        try {
            return \Bitrix\Main\Web\Json::decode($result);
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function pricingCalculatorAddress(string $address, int $price, int $weight)
    {
        if(empty($address)) return;
        $this->data = [
            'client_price' => $price,
            'destination' => [
                'address' => $address
            ],
            'payment_method' => 'already_paid',
            'source' => [
                'platform_station_id' => $this->PLATFORM_ID
            ],
            'tariff' => 'time_interval',
            'total_assessed_price' => $price,
            'total_weight' => $weight,
        ];
        return $this->requestPost('pricing-calculator');
    }

    public function pricingCalculatorPickpoint(string $id, int $price, int $weight)
    {
        if(empty($id)) return;
        $this->data = [
            'client_price' => $price,
            'destination' => [
                'platform_station_id' => $id
            ],
            'payment_method' => 'already_paid',
            'source' => [
                'platform_station_id' => $this->PLATFORM_ID
            ],
            'tariff' => 'time_interval',
            'total_assessed_price' => $price,
            'total_weight' => $weight,
        ];
        return $this->requestPost('pricing-calculator');
    }

    public function getPosts(float $lat = 0, float $lon = 0)
    {
        if($lat <= 0 || $lon <= 0) return false;
        $this->data = [
            "available_for_dropoff" => true,
            "latitude" => [
              "from" => ($lat - 0.08),
              "to" => ($lat + 0.08)
            ],
            "longitude" => [
              "from" => ($lon - 0.12),
              "to" => ($lon + 0.12)
            ],
            "payment_method" => (string) 'already_paid',
            "type" => (string) 'pickup_point'
        ];
        return $this->requestPost('pickup-points/list');
    }

    public function offersCreate($orderId,$postfix='',$method = 'Courier')
    {
        if(empty($orderId)) return;
        $order = Sale\Order::load($orderId);
        if(!$order->isPaid()) return false;

        $propertyCollection = $order->getPropertyCollection();
        $basket = $order->getBasket();
        
        $address = $propertyCollection->getItemByOrderPropertyCode('ADDRESS_STRING')->getValue();
        $postal_code = $propertyCollection->getItemByOrderPropertyCode('ZIP')->getValue();
        $email = $propertyCollection->getItemByOrderPropertyCode('EMAIL')->getValue();
        $phone = $propertyCollection->getItemByOrderPropertyCode('PHONE')->getValue();
        $lastName = $propertyCollection->getItemByOrderPropertyCode('LAST_NAME')->getValue();
        $name = $propertyCollection->getItemByOrderPropertyCode('NAME')->getValue();
        $comment = $propertyCollection->getItemByOrderPropertyCode('ADDRESS_COMMENT')->getValue();
        $pickPointId = $propertyCollection->getItemByOrderPropertyCode('OZON_DELIVERY_VARIANT_ID')->getValue();
        $orderWeight = 0;
        $orderItemsPrice = 0;

        foreach($basket as $basketItem):
            if ($basketItem->isBundleParent()) {

                // Коллекция товаров комплекта.
                $obBundleCollection = $basketItem->getBundleCollection();
    
                /** @var \Bitrix\Sale\BasketItem $obBundleItem */
                foreach ($obBundleCollection as $obBundleItem) {

                    $orderWeight += $obBundleItem->getQuantity() * $obBundleItem->getWeight();
                    $products[] = array(
                        'article' => $obBundleItem->getField("PRODUCT_XML_ID"),
                        'billing_details' => [
                            'assessed_unit_price' => round($obBundleItem->getPrice(), 2)*100,
                            'unit_price' => round($obBundleItem->getPrice(), 2)*100
                        ],
                        'count' => $obBundleItem->getQuantity(),
                        'marking_code' => '',
                        'name' => $obBundleItem->getField('NAME'),
                        'physical_dims' => [
                            'dx' => 33,
                            'dy' => 11,
                            'dz' => 23,
                            'predefined_volume' => 0
                        ],
                        'place_barcode' => (string) $order->getID()
                    );
                    $orderItemsPrice += $obBundleItem->getPrice();

                }

            } else {

                $orderWeight += $basketItem->getQuantity() * $basketItem->getWeight();
                $products[] = array(
                    'article' => $basketItem->getField("PRODUCT_XML_ID"),
                    'billing_details' => [
                        'assessed_unit_price' => round($basketItem->getPrice(), 2)*100,
                        'unit_price' => round($basketItem->getPrice(), 2)*100
                    ],
                    'count' => $basketItem->getQuantity(),
                    'marking_code' => '',
                    'name' => $basketItem->getField('NAME'),
                    'physical_dims' => [
                        'dx' => 33,
                        'dy' => 11,
                        'dz' => 23,
                        'predefined_volume' => 0
                    ],
                    'place_barcode' => (string) $order->getID(),
                );
                $orderItemsPrice += $basketItem->getPrice();

            }
        endforeach;

        if($method == 'Courier') {
            $destination = [
                'custom_location' => [
                    'details' => [
                        'comment' => $comment,
                        'full_address' => $address,
                        'postal_code' => $postal_code
                        //'room' => ''
                    ],
                ],
                'type' => 'custom_location'
            ];
        } else {
            $destination = [
                'platform_station' => [
                    'platform_id' => (string) $pickPointId,
                ],
                'type' => 'platform_station'
            ];
        };

        $this->data = [
            'billing_info' => [
                'payment_method' => 'already_paid'
            ],
            'destination' => $destination,
            'info' => [
                'comment' => '',
                'operator_request_id' => (string) $order->getID().$postfix
            ],
            'items' => $products,
            'last_mile_policy' => (($method == 'Courier')?'time_interval':'self_pickup'),
            'places' => [
                [
                    'barcode' => (string) $order->getID(),
                    'description' => '',
                    'physical_dims' => [
                        'weight_gross' => $orderWeight,
                        'dx' => 33,
                        'dy' => 11,
                        'dz' => 23,
                    ]
                ]
            ],
            'recipient_info' => [
                'email' => $email,
                'first_name' => $name,
                'last_name' => $lastName,
                'phone' => $phone
            ],
            'source' => [
                'platform_station' => [
                    'platform_id' => $this->PLATFORM_ID
                ]
            ]
        ];
        
        $result = $this->requestPost('offers/create');
        if($result['offers'][0]['offer_id']) {
            $this->data = [
                "offer_id" => $result['offers'][0]['offer_id']
            ];
            return $this->requestPost('offers/confirm');
        } else
            return $result;
    }
}