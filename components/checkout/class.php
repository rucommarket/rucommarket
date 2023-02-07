<?php
use \Auth\DiGift;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Bitrix\Sale;
use \Bitrix\Sale\Delivery;
use \Bitrix\Sale\PaySystem;
use \Bitrix\Main\Data\Cache;
use \Bitrix\Conversion\Internals\MobileDetect;
use Bitrix\Main\PhoneNumber\Format;
use Bitrix\Main\PhoneNumber\Parser;
//838150
//test_ND5-0P-6ytgjrdybB5ZNIYfxaYawmbHtTbc19mfyulQ
class Checkout extends \CBitrixComponent
{
    protected $componentPage = '';
    protected $basketStorage;

    private $order;
    private $shipment;

    private $deliveryChecked = 0;

    private function sefMode()
    {
        $arDefaultUrlTemplates404 = array(
            "cart" => "index.php",
            "order" => "order/"
        );
        $arDefaultVariableAliases404 = array();
        $arComponentVariables = array(
        );

        $arVariables = array();
    
        $engine = new CComponentEngine($this);
        $arUrlTemplates = CComponentEngine::makeComponentUrlTemplates($arDefaultUrlTemplates404, $this->arParams["SEF_URL_TEMPLATES"]);
        $arVariableAliases = CComponentEngine::makeComponentVariableAliases($arDefaultVariableAliases404, $this->arParams["VARIABLE_ALIASES"]);
        $this->componentPage = $engine->guessComponentPath(
            $this->arParams["SEF_FOLDER"],
            $arUrlTemplates,
            $arVariables
        );
 
        CComponentEngine::initComponentVariables($this->componentPage, $arComponentVariables, $arVariableAliases, $arVariables);
    }

    public function deleteBasket($id)
    {
        if($id<1)
            return;
        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
        if($basket->getItemById($id)) {
            $basket->getItemById($id)->delete();
            $basket->save();
        }
    }

    protected function initializeBasketOrderIfNotExists(Sale\Basket $basket)
	{
		if (!$basket->getOrder())
		{
            global $USER;
            if(!is_object($USER)) $USER = new \CUser();
			$userId = $USER->getId() ?? 0;

			$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
			/** @var Sale\Order $orderClass */
			$orderClass = $registry->getOrderClassName();

			$order = $orderClass::create($this->getSiteId(), $userId);

			$result = $order->appendBasket($basket);
			if (!$result->isSuccess())
			{
				$this->errorCollection->add($result->getErrors());
			}

			$discounts = $order->getDiscount();
			$showPrices = $discounts->getShowPrices();
			if (!empty($showPrices['BASKET']))
			{
				foreach ($showPrices['BASKET'] as $basketCode => $data)
				{
					$basketItem = $basket->getItemByBasketCode($basketCode);
					if ($basketItem instanceof Sale\BasketItemBase)
					{
						$basketItem->setFieldNoDemand('BASE_PRICE', $data['SHOW_BASE_PRICE']);
						$basketItem->setFieldNoDemand('PRICE', $data['SHOW_PRICE']);
						$basketItem->setFieldNoDemand('DISCOUNT_PRICE', $data['SHOW_DISCOUNT']);
					}
				}
			}
		}
	}

    private function actualQuantity(&$basket)
    {
        $basketItems = $basket->getBasketItems();
        foreach($basketItems as $item):
            $productQuantity = \Bitrix\Catalog\ProductTable::getList([
                'select' => ['QUANTITY'],
                'filter' => ['=ID'=>$item->getProductID()],
            ])->fetch()['QUANTITY'];
            if($productQuantity < $item->getQuantity() && $productQuantity == 0) {
                $item->setField('CAN_BUY','N');
            } else {
                $item->setField('CAN_BUY','Y');
            }
        endforeach;
        $basket->save();
    }

    private function getCart($templateOrder = false)
    {
        global $USER;
        if(!is_object($USER)) $USER = new \CUser();
        if(!Loader::includeModule('sale')) return false;
        if(!Loader::includeModule('catalog')) return false;

        if(!isset($this->arParams['COLUMN_LIST']) || empty($this->arParams['COLUMN_LIST']))
        $this->arResult['COLUMN_LIST'] = [
            "NAME",
            "PRICE",
            "QUANTITY",
            "SUMM"
        ];
        else
        $this->arResult['COLUMN_LIST'] = $this->arParams['COLUMN_LIST'];
        $arBasket = &$this->arResult['BASKET'];

        if (!isset($this->basketStorage))
		{
			$this->basketStorage = Sale\Basket\Storage::getInstance(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
		}
        $basketStorage = $this->basketStorage;
        /** @var Sale\Basket $basket */
        $basket = $basketStorage->getBasket();
        $arBasket['OLD_QUANTITY'] = $basket->getQuantityList();
        $this->actualQuantity($basket);
        $orderableBasket = $basketStorage->getOrderableBasket();
        $basketItems = $basket->getBasketItems();


        $this->initializeBasketOrderIfNotExists($orderableBasket);
        $context = new Sale\Discount\Context\Fuser($basket->getFUserId());
        $discounts = Sale\Discount::buildFromBasket($basket, $context);
        if($discounts) {
            $arDiscounts = $discounts->getApplyResult(true);
            $this->arResult['FULL_DISCOUNT_LIST'] = $arDiscounts['FULL_DISCOUNT_LIST'];
            $this->arResult['APPLIED_DISCOUNT_LIST'] = $arDiscounts['APPLIED_DISCOUNT_LIST'];
            $dData = $discounts->calculate()->getData();
            if (isset($dData['BASKET_ITEMS']))
                $basket->applyDiscount($dData['BASKET_ITEMS']);
        }

        $userId = $USER instanceof CUser? $USER->getId() : null;
		$this->giftManager = \Bitrix\Sale\Discount\Gift\Manager::getInstance()->setUserId($userId);
        \Bitrix\Sale\Compatible\DiscountCompatibility::stopUsageCompatible();
        $collections = array();
		if(!empty($this->arResult['FULL_DISCOUNT_LIST']))
		{
			$collections = $this->giftManager->getCollectionsByBasket(
				$basket,
				$this->arResult['FULL_DISCOUNT_LIST'],
				$this->arResult['APPLIED_DISCOUNT_LIST']
			);
		}
        \Bitrix\Sale\Compatible\DiscountCompatibility::revertUsageCompatible();
		foreach($collections as $collection)
		{
            $productIds = array();
			foreach($collection as $gift)
			{
				$productIds[] = $gift->getProductId();
			}
			unset($gift);
            $giftCollections[] = $productIds;
            unset($productIds);
		}
		unset($collection);

        foreach($giftCollections as $giftCollection):
            $arProducts = [];
            foreach($giftCollection as $gift){
                $arProducts[$gift] = \Bitrix\Catalog\ProductTable::getList([
                    'select'=>[
                        'ID',
                        'QUANTITY',
                        'WEIGHT',
                        'NAME' => 'IBLOCK_ELEMENT.NAME',
                        'IBLOCK_ID' => 'IBLOCK_ELEMENT.IBLOCK_ID',
                    ],
                    'filter'=>['ID'=>$gift]
                ])->Fetch();
            }
            if(is_countable($arProducts) && count($arProducts) === 1) {
                $idGift = array_values($arProducts)[0]['ID'];
                self::chooseGift($idGift,$basketItems);
            } elseif(is_countable($arProducts) && count($arProducts) > 1) {
                $this->arResult['GIFTS_COLLECTION'][] = $arProducts;
            }
            unset($arProducts);
        endforeach;

        foreach($basketItems as $item):
            $basketPropertyCollection = $item->getPropertyCollection();
            if($basketPropertyCollection->getPropertyValues()['IS_GIFT']['VALUE'] == 'Y' && $item->getFinalPrice() > 0) {
                $item->delete();
                $basket->save();
                continue;
            }
        endforeach;

        if($USER->isAuthorized()) {
            $userID = $USER->getID();
        } else {
            //$userID = \CSaleUser::GetAnonymousUserID();
            $userID = 863;
        }
        if($templateOrder) {
        $order = Sale\Order::create(SITE_ID,$userID);
        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
        $order->setBasket($basket->getOrderableItems());
        $basket = $order->getBasket();
        }
        $basketItems = $basket->getBasketItems();

        $arBasket = array_merge($arBasket,[
            'PRICE' => Sale\PriceMaths::roundPrecision($basket->getPrice()),
            'FULL_PRICE' => Sale\PriceMaths::roundPrecision($basket->getBasePrice()),
            'ORDER_PRICE' => Sale\PriceMaths::roundPrecision($orderableBasket->getPrice()),
            'WEIGHT' => $orderableBasket->getWeight()
        ]);
        $arBasket['ITEMS'] = [];
        foreach($basketItems as $item):
            /*if($item->canBuy() && !$item->isDelay())
                $item = $orderableBasket->getItemByBasketCode($item->getBasketCode());*/
            $arItem = [
                'ID' => $item->getId(),
                'PRODUCT_ID' => $item->getProductId(),
                'PRICE' => Sale\PriceMaths::roundPrecision($item->getPrice()),
                'BASE_PRICE' => Sale\PriceMaths::roundPrecision($item->getBasePrice()),
                'QUANTITY' => $item->getQuantity(),
                'FINAL_PRICE' => Sale\PriceMaths::roundPrecision($item->getFinalPrice()),
                'WEIGHT' => $item->getWeight(),
                'CAN_BUY' => $item->canBuy()
            ];
            $basketPropertyCollection = $item->getPropertyCollection();
            $arItem['PROPERTIES'] = $basketPropertyCollection->getPropertyValues();
            $arItem['PRODUCT'] = \Bitrix\Catalog\ProductTable::getList([
                'select'=>[
                    'ID',
                    'QUANTITY',
                    'WEIGHT',
                    'NAME' => 'IBLOCK_ELEMENT.NAME',
                    'IBLOCK_ID' => 'IBLOCK_ELEMENT.IBLOCK_ID',
                    'PREVIEW_PICTURE' => 'IBLOCK_ELEMENT.PREVIEW_PICTURE',
                    'CODE' => 'IBLOCK_ELEMENT.CODE',
                    'IBLOCK_SECTION_ID' => 'IBLOCK_ELEMENT.IBLOCK_SECTION_ID',
                    'DETAIL_PAGE_URL' => 'IBLOCK_ELEMENT.IBLOCK.DETAIL_PAGE_URL',
                ],
                'filter'=>['ID'=>$item->getProductId()]
            ])->Fetch();
            $arItem['PRODUCT']['URL'] = 
                \CIBlock::ReplaceDetailUrl( $arItem['PRODUCT']['DETAIL_PAGE_URL'], $arItem['PRODUCT'],false,'E');

            if($arItem['PROPERTIES']['IS_GIFT']['VALUE'] == 'Y') {
                array_push($arBasket['ITEMS'],$arItem);
            } else {
                array_unshift($arBasket['ITEMS'],$arItem);
            }
        endforeach;
    }

    private function getAddress()
    {
        global $USER;
        if(!is_object($USER)) $USER = new \CUser();
        if(!Loader::includeModule('sale')) return false;
        $arResult = &$this->arResult;
        $cache = Cache::createInstance();
        if($cache->initCache(7200, "address_".Sale\Fuser::getId(),'addresses')) {
            $arResult['ADDRESS'] = $cache->getVars();
        } else {
            return;
        }
    }

    private function checkAddress()
    {
        global $USER;
        if(!is_object($USER)) $USER = new \CUser();
        if(!Loader::includeModule('sale')) return false;
        $request = Application::getInstance()->getContext()->getRequest();
        $cache = Cache::createInstance();
        $arResult = &$this->arResult;
        $arResult['ADDRESS'] = [
            'STRING' => $request->getPost('address_string'),
            'KLADR' => $request->getPost('address_kladr'),
            'INDEX' => $request->getPost('address_index'),
            'REGION' => $request->getPost('address_region'),
            'CITY' => $request->getPost('address_city'),
            'STREET' => $request->getPost('address_street'),
            'HOUSE' => $request->getPost('address_house'),
            'BUILDING' => $request->getPost('address_building'),
            'FLAT' => $request->getPost('address_flat'),
            'COMMENT' => $request->getPost('address_comment'),
            'LAT' => $request->getPost('address_lat'),
            'LON' => $request->getPost('address_lon'),
        ];
        $cache->clean("address_".Sale\Fuser::getId(),'addresses');
        if ($cache->startDataCache(7200, "address_".Sale\Fuser::getId(),'addresses')) {
            $cache->endDataCache($arResult['ADDRESS']);
        }
    }

    protected function getOrderClone($order)
	{
		$orderClone = $order->createClone();

        $clonedShipment = NULL;
        foreach ($order->getShipmentCollection() as $shipment)
		{
			if (!$shipment->isSystem())
                $clonedShipment =  $shipment;
		}

		if (!empty($clonedShipment))
		{
			$clonedShipment->setField('CUSTOM_PRICE_DELIVERY', 'N');
		}

		return $orderClone;
	}

    private function getDelivery()
    {
        $order = &$this->order;
        $shipment = &$this->shipment;
        $arDeliveries = &$this->arResult['DELIVERIES'];
        $arMulti = &$this->arResult['DELIVERIES_MULTI'];
        $arMulti = [
            0=>false,
            1=>false,
            'CHECKED'=>-1,
            'PRICE' => [
                0 => -1,
                1 => -1
            ]
        ];
        $services = Sale\Delivery\Services\Manager::getRestrictedObjectsList($shipment);
        $arDelivery = [];
        foreach ($services as $deliveryId => $deliveryObj)
		{
            if ((int)$shipment->getDeliveryId() === $deliveryId)
			{
				$calcResult = $deliveryObj->calculate($shipment);
				$calcOrder = $order;
			}
			else
			{
				if (empty($orderClone))
				{
					$orderClone = self::getOrderClone($order);
				}
				$orderClone->isStartField();
                $clonedShipment = NULL;
                foreach ($orderClone->getShipmentCollection() as $ship) {
			        if (!$ship->isSystem())
                        $clonedShipment =  $ship;
		        }
				$clonedShipment->setField('DELIVERY_ID', $deliveryId);
				$calculationResult = $orderClone->getShipmentCollection()->calculateDelivery();

				if ($calculationResult->isSuccess())
				{
					$calcDeliveries = $calculationResult->get('CALCULATED_DELIVERIES');
					$calcResult = reset($calcDeliveries);
				}
				else
				{
					$calcResult = new Delivery\CalculationResult();
					$calcResult->addErrors($calculationResult->getErrors());
				}

				$orderClone->doFinalAction(true);
				$calcOrder = $orderClone;
			}
            if ($calcResult->isSuccess()) {
					$arDelivery['PRICE'] = Sale\PriceMaths::roundPrecision($calcResult->getPrice());
					$arDelivery['PRICE_FORMATED'] = SaleFormatCurrency($arDelivery['PRICE'], $calcOrder->getCurrency());
                    $arDelivery['ID'] = $deliveryId;
                    $arDelivery["NAME"] = $deliveryObj->getNameWithParent();
					$currentCalcDeliveryPrice = Sale\PriceMaths::roundPrecision($calcOrder->getDeliveryPrice());
					if ($currentCalcDeliveryPrice >= 0 && $arDelivery['PRICE'] != $currentCalcDeliveryPrice)
					{
						$arDelivery['DELIVERY_DISCOUNT_PRICE'] = $currentCalcDeliveryPrice;
						$arDelivery['DELIVERY_DISCOUNT_PRICE_FORMATED'] = SaleFormatCurrency($arDelivery['DELIVERY_DISCOUNT_PRICE'], $calcOrder->getCurrency());
					}
					if ($calcResult->getPeriodDescription() <> '')
					{
						$arDelivery['PERIOD_TEXT'] = $calcResult->getPeriodDescription();
					}
                    $arDelivery['DATA'] = ($calcResult->getTmpData())?:[
                        'MULTIVARIANT' => true
                    ];
                    
                    $arMulti[$arDelivery['DATA']['MULTIVARIANT']] = true;
                    if($arMulti['PRICE'][$arDelivery['DATA']['MULTIVARIANT']] < 0) {
                        $arMulti['PRICE'][$arDelivery['DATA']['MULTIVARIANT']] = $arDelivery['PRICE'];
                    } else {
                        if($arMulti['PRICE'][$arDelivery['DATA']['MULTIVARIANT']] > $arDelivery['PRICE'])
                            $arMulti['PRICE'][$arDelivery['DATA']['MULTIVARIANT']] = $arDelivery['PRICE'];
                    }
                    if($this->deliveryChecked == $arDelivery['ID']) {
                        $arDelivery['CHECKED'] = 'Y';
                        $arMulti['CHECKED'] = $arDelivery['DATA']['MULTIVARIANT'];
                    }
                    $arDelivery['CALCULATE_DESCRIPTION'] = $calcResult->getDescription();
			}
			else
			{
				if (count($calcResult->getErrorMessages()) > 0)
				{
					foreach ($calcResult->getErrorMessages() as $message)
					{
						$arDelivery['CALCULATE_ERRORS'] .= $message.'<br>';
					}
				}
				else
				{
					$arDelivery['CALCULATE_ERRORS'] = 'Неизвестная ошибка';
				}
			}
            $arDelivery['CALCULATE_DESCRIPTION'] = $calcResult->getDescription();
            //добавляем дни к выводу срока доставки
            if ($arDelivery['DATA']['DELIVERY_TEXT']) {
                $ADD_COUNT_DAYS = 3;
                $daysDeclension = new \Bitrix\Main\Grid\Declension('день', 'дня', 'дней');
                $intDays = (int)filter_var($arDelivery['DATA']['DELIVERY_TEXT'], FILTER_SANITIZE_NUMBER_INT);
                if ($intDays && $intDays > 0) {
                    $arDelivery['DATA']['DELIVERY_TEXT'] = "До " . ($intDays + $ADD_COUNT_DAYS) . " рабочих " . $daysDeclension->get(($intDays + $ADD_COUNT_DAYS));
                }
            }
            if($calcResult->isSuccess()):
                foreach (GetModuleEvents('sale', 'OnSaleComponentDeliveryAfterCalculation', true) as $arEvent)
                {
                    ExecuteModuleEventEx($arEvent, [&$arDelivery,$order]);
                }
                $arDeliveries[$arDelivery['ID']] = $arDelivery; 
            endif;
            unset($arDelivery);
        }
    }

    private function getPVZ()
    {
        $arPVZ = [44,42,53];
        return $arPVZ;
    }

    private function checkDelivery($deliveryID = '')
    {
        $_SESSION['BX_DELIVERY_ID_CHECKED'] = $deliveryID;
        unset($_SESSION['BX_DELIVERY_MULTI_ID_CHECKED']);
        unset($_SESSION['BX_DELIVERY_MULTI_NAME_CHECKED']);
    }

    private function checkDeliveryMulti($deliveryMultiID,$deliveryMultiName)
    {
        $_SESSION['BX_DELIVERY_MULTI_ID_CHECKED'] = $deliveryMultiID;
        $_SESSION['BX_DELIVERY_MULTI_NAME_CHECKED'] = $deliveryMultiName;
    }

    private function getPersonType()
    {
        return 1;
    }

    private function getPayment()
    {
        $order = &$this->order;
        $personTypeId = $order->getPersonTypeId();
        $arPayments = &$this->arResult['PAYMENTS'];
        $innerPaySystemId = PaySystem\Manager::getInnerPaySystemId();
        $paySystemResult = PaySystem\Manager::getList(array(
            'filter'  => array('ACTIVE' => 'Y'),
            'order' => array('SORT' => 'ASC', 'NAME' => 'ASC'),
        ));
        $arPayments = [];
        while($paySystem = $paySystemResult->fetch())
        {
            if($paySystem['ID'] == $innerPaySystemId)
                continue;
            $dbRestriction = Sale\Internals\ServiceRestrictionTable::getList(array(
                'select' => array('PARAMS'),
                'filter' => array(
                    'SERVICE_ID' => $paySystem['ID'],
                    'SERVICE_TYPE' => Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT
                )
            ));
            $restrictions = array();
            while ($restriction = $dbRestriction->fetch())
                $restrictions = array_merge($restrictions,$restriction['PARAMS']);
            
            if(is_array($restrictions['SITE_ID']) && !in_array(SITE_ID,$restrictions['SITE_ID']))
                continue;

            if(is_array($restrictions['PERSON_TYPE_ID']) && count($restrictions['PERSON_TYPE_ID'])>0 && !empty($personTypeId))
            {
                foreach($restrictions['PERSON_TYPE_ID'] as $pid)
                    if($personTypeId==$pid)
                        $arPayments[$paySystem['PAY_SYSTEM_ID']]=$paySystem;
            } else {
                $arPayments[$paySystem['PAY_SYSTEM_ID']]=$paySystem;
            }
        }
    }
    private function checkPayment($paymentID = '')
    {
        $_SESSION['BX_PAYMENT_ID_CHECKED'] = $paymentID;
    }

    private function setPaymentId()
    {
        $order = &$this->order;
        self::getPayment();
        $arPayments = &$this->arResult['PAYMENTS'];

        if(!empty($arPayments) && is_array($arPayments)) {
            $paymentCollection = $order->getPaymentCollection();
            $pay = 0;
            $arPays = [];
            foreach($arPayments as $arPay) {
                $arPays[] = $arPay['ID'];
            }
            if(isset($_SESSION['BX_PAYMENT_ID_CHECKED'])
                && !empty($_SESSION['BX_PAYMENT_ID_CHECKED'])
                && in_array($_SESSION['BX_PAYMENT_ID_CHECKED'],$arPays)) {
                    $pay = (int)$_SESSION['BX_PAYMENT_ID_CHECKED'];
            } else {
                foreach ($arPayments as $item) {
                    $pay = (int)$item['ID'];
                    break;
                }
            }
            $arPayments[$pay]['CHECKED'] = 'Y';

            $payment = $paymentCollection->createItem(
                \Bitrix\Sale\PaySystem\Manager::getObjectById($pay)
            );
            $payment->setField("SUM", $order->getPrice());
            $payment->setField("CURRENCY", $order->getCurrency());
        }

    }

    private function setContact()
    {
        $arContact = &$this->arResult['CONTACT'];
        $request = Application::getInstance()->getContext()->getRequest();
        $cache = Cache::createInstance();
        $arContact = [
            'PHONE' => $request->getPost('PHONE'),
            'EMAIL' => $request->getPost('EMAIL'),
            'NAME' => $request->getPost('NAME'),
            'LAST_NAME' => $request->getPost('LAST_NAME'),
            'SECOND_NAME' => $request->getPost('SECOND_NAME')
        ];
        $cache->clean("contact_".Sale\Fuser::getId(),'order_contact');
        if ($cache->startDataCache(7200, "contact_".Sale\Fuser::getId(),'order_contact')) {
            $cache->endDataCache($arContact);
        }
        $return = [];
        $parsedPhone = Parser::getInstance()->parse($request->getPost('PHONE'));
        if(empty(trim($request->getPost('PHONE'))))
            $return['PHONE'] = [
                "MESSAGE" => "Отсутствует номер телефона"
            ];
        if(!$parsedPhone->isValid()) {
            $return['PHONE'] = [
                "MESSAGE" => "Некорректный номер телефона"
            ];
        }
        if(empty(trim($request->getPost('EMAIL'))))
            $return['EMAIL'] = [
                "MESSAGE" => "Отсутствует E-mail"
            ];
        global $USER;
        if(!is_object($USER)) $USER = new \CUser();
        if($USER->IsAuthorized() && in_array(45, $USER->GetUserGroup($USER->GetID()))) {
            if(strpos($request->getPost('EMAIL'),'@authentica.ru') ||
                strpos($request->getPost('EMAIL'),'@ibt.ru') ||
                strpos($request->getPost('EMAIL'),'@senecapeople.ru')
                )
            $return['EMAIL'] = [
                "MESSAGE" => "Некорректный E-mail"
            ];
        }
        if(!check_email($request->getPost('EMAIL')))
            $return['EMAIL'] = [
                "MESSAGE" => "Некорректный E-mail"
            ];
        if(empty(trim($request->getPost('NAME'))))
            $return['NAME'] = [
                "MESSAGE" => "Отсутствует Имя"
            ];
        if(empty(trim($request->getPost('LAST_NAME'))))
            $return['LAST_NAME'] = [
                "MESSAGE" => "Отсутствует Фамилия"
            ];
        return $return;
    }

    private function getContact()
    {
        $arContact = &$this->arResult['CONTACT'];
        $cache = Cache::createInstance();
        if($cache->initCache(7200, "contact_".Sale\Fuser::getId(),'order_contact')) {
            $arContact = $cache->getVars();
        }
    }

    private function getCoupon()
    {
        if(!Loader::includeModule('sale')) return false;
        $arCoupons = &$this->arResult['COUPONS'];
        $arCoupons = \Bitrix\Sale\DiscountCouponsManager::get();
    }

    private function getOrder()
    {
        global $USER;
        if(!is_object($USER)) $USER = new \CUser();
        if(!Loader::includeModule('sale')) return false;
        if(!Loader::includeModule('catalog')) return false;
        $arResult = &$this->arResult;

        if($USER->isAuthorized()) {
            $userID = $USER->getID();
        } else {
            //$userID = \CSaleUser::GetAnonymousUserID();
            $userID = 863;
        }

        Sale\DiscountCouponsManager::init();

        $order = Sale\Order::create(SITE_ID,$userID);
        $this->order = &$order;

        $order->setPersonTypeId(self::getPersonType());
        self::setPaymentId();
        
        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());

        $order->setBasket($basket->getOrderableItems());

        $shipmentCollection = $order->getShipmentCollection();
		$shipment = $shipmentCollection->createItem();
        $this->shipment = &$shipment;

        $shipmentItemCollection = $shipment->getShipmentItemCollection();
		$shipment->setField('CURRENCY', $order->getCurrency());

		foreach ($order->getBasket() as $item)
		{
            /** @var Sale\ShipmentItem $shipmentItem */
			$shipmentItem = $shipmentItemCollection->createItem($item);
			$shipmentItem->setQuantity($item->getQuantity());
		}
//unset($_SESSION['BX_DELIVERY_ID_CHECKED']);
        if(isset($_SESSION['BX_DELIVERY_ID_CHECKED']) && !empty($_SESSION['BX_DELIVERY_ID_CHECKED'])) {
            $DeliveryId = (int)$_SESSION['BX_DELIVERY_ID_CHECKED'];
            $serviceObj = Sale\Delivery\Services\Manager::getObjectById($DeliveryId);
            $shipment->setDeliveryService($serviceObj);
        } else {
            $DeliveryId = (int)$shipment->getDeliveryId();
        }

        $service = Sale\Delivery\Services\Manager::getById($DeliveryId);

        $this->deliveryChecked = $DeliveryId;
        
        $shipment->setFields([
            'DELIVERY_ID' => $DeliveryId,
            'DELIVERY_NAME' => $service['NAME'],
            'CURRENCY' => $order->getCurrency()
        ]);

        foreach ($shipmentCollection as $shipmentItem) {
            if (!$shipmentItem->isSystem()) {
                $shipmentItem->allowDelivery();
                break;
            }
        }

        $discounts = $order->getDiscount();
        $discounts->calculate();
        $discountData = $discounts->getApplyResult();

        $arResult['DISCOUNT_LIST'] = $discountData['DISCOUNT_LIST'];

        foreach($discountData['COUPON_LIST'] as $coupon) {
            $arResult['DISCOUNT_LIST'][$coupon['ORDER_DISCOUNT_ID']]['COUPONS'][$coupon['COUPON']] = $coupon;
        }
        
        $propertyCollection = $order->getPropertyCollection();
        if(isset($arResult['ADDRESS']) && !empty($arResult['ADDRESS'])) {
            $arAddress = $arResult['ADDRESS'];
            $arProperties = [
                'ADDRESS_STRING' => $arAddress['STRING'],
                'CITY_TO' => $arAddress['CITY'],
                'REGION_TO' => $arAddress['REGION'],
                'KLADR' => $arAddress['KLADR'],
                'ADDRESS' =>  $arAddress['STREET'],
                'HOUSE' => $arAddress['HOUSE'],
                'BUILDING' => $arAddress['BUILDING'],
                'ROOM' => $arAddress['FLAT'],
                'ZIP' => $arAddress['INDEX'],
                'ADDRESS_COMMENT' => $arAddress['COMMENT']
            ];
        }
        
        foreach($propertyCollection as $prop) {
            $code = $prop->getProperty()['CODE'];
            if(isset($arProperties[$code])) {
                $prop->setValue($arProperties[$code]);
            }
        }

        $shipmentCollection->calculateDelivery();

        $arResult['ORDER'] = [
            'PRICE' => Sale\PriceMaths::roundPrecision($order->getPrice()),
            'PAYMENT_SYSTEM_ID' => $order->getPaySystemIdList(),
            'DELIVERY_ID' => $order->getDeliveryIdList(),
            'DELIVERY_PRICE' => Sale\PriceMaths::roundPrecision($order->getDeliveryPrice()),
            'PERSON_TYPE_ID' => $order->getPersonTypeId(),
            'DATE_INSERT' => $order->getDateInsert(),
            'CANCELED' => $order->isCanceled()
        ];

        $order->doFinalAction(true);
    }

    private function addCoupon($coupon)
    {
        if(\Bitrix\Sale\DiscountCouponsManager::isExist($coupon)) {
            return \Bitrix\Sale\DiscountCouponsManager::add($coupon);
        } else {
            $this->arResult['ERRORS']['PROMO'] = 'Промокод не действителен';
            return false;
        }
    }

    private function removeCoupon($coupon)
    {
        return \Bitrix\Sale\DiscountCouponsManager::delete(trim($coupon));
    }

    private function digiftCardAdd($card='',$pin='')
    {
        if (
            !Loader::includeModule("auth.digift")
        ) {
            return false;
        }

        /** @var bool Признак ошибки при выполнении операции. */
        $bError = false;
        /** @var string Общее сообщение об ошибке. */
        $sMessage = "";
        /** @var array Ошибки по каждому полю формы. */
        $arData = [];
        $arResultDigift = &$this->arResult['DIGIFT'];

        $obStorage = new DiGift\Storage(new ArrayObject([
            "CARD_CODE" => (string)$card,
            "PIN" => (string)$pin
        ]));
        $obResult = $obStorage->save();
        if (!$obResult->isSuccess()) {
            $bError = true;
            /** @var Main\Error $obError */
            $obError = $obResult->getErrorCollection()->current();
            $arCustomData = $obError->getCustomData();
            if (!empty($arCustomData["ERROR_FIELDS"])) {
                /** @var Main\ErrorCollection $obErrorFields */
                $obErrorFields = $arCustomData["ERROR_FIELDS"];
                foreach ($obErrorFields as $sFieldName => $obErrorField) {
                    $arData[$sFieldName] = $obErrorField->getMessage();
                }
            }
            $sMessage = nl2br($obError->getMessage());
        }

        $arResultDigift['error'] = $bError;
        $arResultDigift["message"] = $sMessage;
        $arResultDigift["data"] = $arData;
    }

    private function digiftCardDelete()
    {
        if (
            !Loader::includeModule("auth.digift")
        ) {
            $arResultDigift['error'] = true;
            $arResultDigift["message"] = 'модуль не подключен';
        }

        /** @var bool Признак ошибки при выполнении операции. */
        $bError = false;
        /** @var string Общее сообщение об ошибке. */
        $sMessage = "";
        /** @var array Ошибки по каждому полю формы. */
        $arData = [];

        DiGift\Storage::dropSession();

        $arResultDigift['error'] = $bError;
        $arResultDigift["message"] = $sMessage;
        $arResultDigift["data"] = $arData;
    }

    private function initDigift()
    {
        if (!Loader::includeModule("auth.digift")) return;
        
        $arResult = &$this->arResult["DIGIFT"];

        if (!DiGift\Storage::hasSession()) return;

        try {

            $obStorage = DiGift\Storage::createFromSession();
            $arResult["CARD_CODE"] = (string)$obStorage->getCardCode();
            $obBalance = $obStorage->getBalance();
            if (isset($obBalance)) {
                $arResult["BALANCE_AMOUNT"] = (float)$obBalance->offsetGet("AMOUNT");
                $arResult["BALANCE_CURRENCY"] = (string)$obBalance->offsetGet("CURRENCY");
                if (strlen($arResult["BALANCE_CURRENCY"]) == 0) {
                    $arResult["BALANCE_CURRENCY"] = $this->order->getCurrency();
                }
                $arResult["BALANCE_FORMATED"] = SaleFormatCurrency($arResult["BALANCE_AMOUNT"], $arResult["BALANCE_CURRENCY"]);
            }

            $this->arResult['DIGIFT']["ORDER_DIGIFT_PRICE"] = min($this->arResult["ORDER_TOTAL_PRICE"], $arResult["BALANCE_AMOUNT"]);
            $this->arResult['DIGIFT']["ORDER_DIGIFT_PRICE_FORMATED"] = SaleFormatCurrency($this->arResult["ORDER_DIGIFT_PRICE"], $this->order->getCurrency());
            // $this->arResult["ORDER_TOTAL_PRICE"] = max(0, $this->arResult["ORDER_TOTAL_PRICE"] - $arResult["BALANCE_AMOUNT"]);
            // $this->arResult["ORDER_TOTAL_PRICE_FORMATED"] = SaleFormatCurrency($this->arResult["ORDER_TOTAL_PRICE"], $this->order->getCurrency());
            //$this->arResult['DIGIFT']["JS_DATA"]["TOTAL"]["ORDER_DIGIFT_PRICE"] = $this->arResult["ORDER_DIGIFT_PRICE"];
            //$this->arResult['DIGIFT']["JS_DATA"]["TOTAL"]["ORDER_DIGIFT_PRICE_FORMATED"] = $this->arResult["ORDER_DIGIFT_PRICE_FORMATED"];
            // $this->arResult["JS_DATA"]["TOTAL"]["ORDER_TOTAL_PRICE"] = $this->arResult["ORDER_TOTAL_PRICE"];
            // $this->arResult["JS_DATA"]["TOTAL"]["ORDER_TOTAL_PRICE_FORMATED"] = $this->arResult["ORDER_TOTAL_PRICE_FORMATED"];

        } catch (\Bitrix\Main\ArgumentException $obException) {
            DiGift\Storage::dropSession();
        }
    }

    private function parsePhone($phone)
    {
        if(!preg_match('/^((\+7)\s\([0-9]{3}\)\s([0-9]){3}([-])([0-9]){4})$/',$phone)) {
            $ph = $phone;
            $ph = preg_replace('/[^0-9]/', '', $ph);
            if(substr($ph,0,1) != '7' && strlen($ph) == 10) $ph = '7'.$ph;
            if(substr($ph,0,1) == '8' && strlen($ph) == 11) $ph = '7'.substr($ph,1,10);
            if(strlen($ph) == 11) $phone = phone_format($ph,'+# (###) ###-####');
        }
        return $phone;
    }

    private function saveOrder()
    {
        if(!Loader::includeModule('sale')) return false;
        $order = &$this->order;
        $arResult = &$this->arResult;
        $arAddress = $arResult['ADDRESS'];
        $propertyCollection = $order->getPropertyCollection();
        $phone = self::parsePhone($arResult['CONTACT']['PHONE']);
        $arProperties = [
            'PHONE' => $phone,
            'EMAIL' => $arResult['CONTACT']['EMAIL'],
            'NAME' => $arResult['CONTACT']['NAME'],
            'LAST_NAME' => $arResult['CONTACT']['LAST_NAME'],
            'SECOND_NAME' => $arResult['CONTACT']['SECOND_NAME'],
            'ADDRESS_STRING' => $arAddress['STRING'],
            'CITY_TO' => $arAddress['CITY'],
            'REGION_TO' => $arAddress['REGION'],
            'KLADR' => $arAddress['KLADR'],
            'ADDRESS' =>  $arAddress['STREET'],
            'HOUSE' => $arAddress['HOUSE'],
            'BUILDING' => $arAddress['BUILDING'],
            'ROOM' => $arAddress['FLAT'],
            'ZIP' => $arAddress['INDEX'],
            'ADDRESS_COMMENT' => $arAddress['COMMENT'],
        ];
        if(isset($_SESSION['BX_DELIVERY_MULTI_ID_CHECKED']))
        $arProperties['OZON_DELIVERY_VARIANT_ID'] = $_SESSION['BX_DELIVERY_MULTI_ID_CHECKED'];
        if(isset($_SESSION['BX_DELIVERY_MULTI_NAME_CHECKED']))
        $arProperties['PICKUP_POINT'] = $_SESSION['BX_DELIVERY_MULTI_NAME_CHECKED'];

        foreach($propertyCollection as $prop) {
            $code = $prop->getProperty()['CODE'];
            if(isset($arProperties[$code])) {
                $prop->setValue($arProperties[$code]);
            }
        }

        foreach (GetModuleEvents('sale', 'OnSaleComponentOrderBeforeSave', true) as $arEvent)
        {
            ExecuteModuleEventEx($arEvent, [&$order,&$arResult['ERRORS']['ORDER']]);
        }

        if($arResult['ERRORS']['ORDER']) {
            return false;
        }
        
        $resOrder = $order->save();

        foreach (GetModuleEvents('sale', 'OnSaleComponentOrderAfterSave', true) as $arEvent)
        {
            ExecuteModuleEventEx($arEvent, [$resOrder, $order, &$arResult['ERRORS']['ORDER']]);
        }

        if($arResult['ERRORS']['ORDER']) {
            return false;
        }

        return true;
    }

    private function chooseGift($idGift = 0, $basketItems = null)
    {
        if($basketItems) {
            foreach($basketItems as $item):
                if ($idGift == $item->getProductID()) return;
            endforeach;
        }
        if($idGift < 1)
            return;
        $obResult = \Bitrix\Catalog\Product\Basket::addProduct(
            [
                "PRODUCT_ID" => $idGift,
                "QUANTITY" => 1,
                "PRODUCT_PROVIDER_CLASS" => "\\Auth\\Domino\\ProductProvider",
                'PROPS' => [
                    [
                        'NAME' => 'Подарок',
                        'CODE' => 'IS_GIFT',
                        'VALUE' => 'Y',
                        'SORT' => 100
                    ],
                ],
            ]
        );
    }

    public function getBasket($orderId = null)
    {
        if(!$orderId) return;
        
        $order = Sale\Order::load($orderId);
        $this->arResult['ORDER'] = [
            'PRICE' => Sale\PriceMaths::roundPrecision($order->getPrice()),
            'PAYMENT_SYSTEM_ID' => $order->getPaySystemIdList(),
            'DELIVERY_ID' => $order->getDeliveryIdList(),
            'DELIVERY_PRICE' => Sale\PriceMaths::roundPrecision($order->getDeliveryPrice()),
            'PERSON_TYPE_ID' => $order->getPersonTypeId(),
            'DATE_INSERT' => $order->getDateInsert(),
            'CANCELED' => $order->isCanceled()
        ];
        $basket = $order->getBasket();
        $basketItems = $basket->getBasketItems();
        $arBasket = &$this->arResult['BASKET'];
        $arBasket = [
            'PRICE' => Sale\PriceMaths::roundPrecision($basket->getPrice()),
            'FULL_PRICE' => Sale\PriceMaths::roundPrecision($basket->getBasePrice()),
            'WEIGHT' => $basket->getWeight()
        ];
        
        foreach($basketItems as $item):
            if($item->canBuy() && !$item->isDelay())
                $item = $basket->getItemByBasketCode($item->getBasketCode());
            $arBasket['ITEMS'][$item->getId()] = [
                'ID' => $item->getId(),
                'PRODUCT_ID' => $item->getProductId(),
                'PRICE' => Sale\PriceMaths::roundPrecision($item->getPrice()),
                'BASE_PRICE' => Sale\PriceMaths::roundPrecision($item->getBasePrice()),
                'QUANTITY' => $item->getQuantity(),
                'FINAL_PRICE' => Sale\PriceMaths::roundPrecision($item->getFinalPrice()),
                'WEIGHT' => $item->getWeight(),
                'CAN_BUY' => $item->canBuy()
            ];
            
            $basketPropertyCollection = $item->getPropertyCollection();
            $arBasket['ITEMS'][$item->getId()]['PROPERTIES'] = $basketPropertyCollection->getPropertyValues();
            $arBasket['ITEMS'][$item->getId()]['PRODUCT'] = \Bitrix\Catalog\ProductTable::getList([
                'select'=>[
                    'ID',
                    'QUANTITY',
                    'WEIGHT',
                    'NAME' => 'IBLOCK_ELEMENT.NAME',
                    'IBLOCK_ID' => 'IBLOCK_ELEMENT.IBLOCK_ID',
                    'PREVIEW_PICTURE' => 'IBLOCK_ELEMENT.PREVIEW_PICTURE',
                    'CODE' => 'IBLOCK_ELEMENT.CODE',
                    'IBLOCK_SECTION_ID' => 'IBLOCK_ELEMENT.IBLOCK_SECTION_ID',
                    'DETAIL_PAGE_URL' => 'IBLOCK_ELEMENT.IBLOCK.DETAIL_PAGE_URL',
                ],
                'filter'=>['ID'=>$item->getProductId()]
            ])->Fetch();
            $arBasket['ITEMS'][$item->getId()]['PRODUCT']['URL'] = 
                \CIBlock::ReplaceDetailUrl( $arBasket['ITEMS'][$item->getId()]['PRODUCT']['DETAIL_PAGE_URL'], $arBasket['ITEMS'][$item->getId()]['PRODUCT'],false,'E');
        endforeach;

    }

    public function executeComponent()
    {
        if(!Loader::includeModule('sale')) return false;
        $detect = new MobileDetect;
        $this->arResult['IS_MOBILE'] = $detect->isMobile();

        global $USER;
        if(!is_object($USER)) $USER = new \CUser();
        $request = Application::getInstance()->getContext()->getRequest();

        self::sefMode();
        if($this->componentPage == 'payment' || ($this->componentPage == 'order' && $request->getPost('action') == 'payment')) {
            if(!isset($_REQUEST['ORDER_ID'])) {
                $resultContact = self::setContact();
                if(empty($resultContact)) {
                    self::getCart(true);
                    self::getAddress();
                    if(!$this->arResult['ADDRESS']) {
                        $this->arResult['ERRORS']['ADDRESS'] = 'Варианты доставки станут доступны, когда вы введете адрес';
                        $this->componentPage = 'order';
                    } elseif(!isset($_SESSION['BX_DELIVERY_ID_CHECKED']) || empty(($_SESSION['BX_DELIVERY_ID_CHECKED']))) {
                        $this->arResult['ERRORS']['DELIVERY'] = 'Выберите вариант доставки';
                        $this->componentPage = 'order';
                    } elseif(in_array($_SESSION['BX_DELIVERY_ID_CHECKED'],self::getPVZ()) && 
                        (
                            !isset($_SESSION['BX_DELIVERY_MULTI_ID_CHECKED']) || empty($_SESSION['BX_DELIVERY_MULTI_ID_CHECKED'])
                        )) {
                        $this->arResult['ERRORS']['DELIVERY'] = 'Выберите пункт выдачи';
                        $this->componentPage = 'order';
                    } else {
                        self::getOrder();
                        if(self::saveOrder()) {
                            $orderId = $this->order->getId();
                            $paymentCollection = $this->order->getPaymentCollection();
                            $_REQUEST['ORDER_ID'] = $orderId;
                            $_REQUEST['PAYMENT_ID'] = $paymentCollection[0]->getID();
                            $_REQUEST['HASH'] = $paymentCollection[0]->getHash();
                            $this->componentPage = 'payment_link';
                            //$this->componentPage = 'payment_none';
                        } else {
                            $this->componentPage = 'order';
                        }
                    }
                } elseif(is_array($resultContact)) {
                    $this->arResult['ERRORS']['CONTACT'] = $resultContact;
                    $this->componentPage = 'order';
                }
            } else {
                self::getBasket((int)$_REQUEST['ORDER_ID']);
                if(isset($_REQUEST['PAYMENT_STATUS']) && $_REQUEST['PAYMENT_STATUS'] == 'success') {
                    $this->componentPage = 'payment_success';
                } else {
                    $this->componentPage = 'payment';
                    /*$d1 = (MakeTimeStamp($this->arResult['ORDER']['DATE_INSERT']) + 90 * 60);
                    $d2 = MakeTimeStamp(new \Bitrix\Main\Type\DateTime());
                    if($d1 > $d2) {
                        $this->componentPage = 'payment';
                        //$this->componentPage = 'payment_none';
                    } else {
                        $this->componentPage = 'payment_timeout';
                    }*/
                }
            }
        }
        if($this->componentPage == 'cart') {
            if($request->getPost('AJAX_CALL') == 'Y') {
                switch ($request->getPost('action')) {
                    case 'delete_basket':
                        self::deleteBasket($request->getPost('id_basket'));
                        break;
                    case 'choose_gift':
                        self::chooseGift($request->getPost('id_gift'));
                        break;
                    default:
                        break;
                }
            }
            self::getCart();
        } elseif($this->componentPage == 'order') {
            //$cache = Cache::createInstance();$cache->clean("address_".Sale\Fuser::getId(),'addresses');
            if($request->getPost('AJAX_CALL') == 'Y') {
                switch ($request->getPost('action')) {
                    case 'add_digift':
                        $this->arResult['BLOCK_DIGIFT_VIEW'] = true;
                        self::digiftCardAdd($request->getPost('card'),$request->getPost('pin'));
                        break;
                    case 'delete_digift':
                        $this->arResult['BLOCK_DIGIFT_VIEW'] = true;
                        self::digiftCardDelete();
                        break;
                    case 'add_coupon':
                        $this->arResult['BLOCK_COUPON_VIEW'] = true;
                        self::addCoupon($request->getPost('coupon'));
                        break;
                    case 'remove_coupon':
                        $this->arResult['BLOCK_COUPON_VIEW'] = true;
                        self::removeCoupon($request->getPost('coupon'));
                        break;
                    case 'check_address':
                        self::checkAddress();
                        break;
                    case 'check_delivery':
                        self::checkDelivery($request->getPost('check_delivery'));
                        break;
                    case 'delivery_multy':
                        self::checkDeliveryMulti($request->getPost('variant_id'),$request->getPost('variant_name'));
                    case 'check_payment':
                        self::checkPayment($request->getPost('check_payment'));
                        break;
                    case 'delete_basket':
                        self::deleteBasket($request->getPost('id_basket'));
                        break;
                    case 'choose_gift':
                        self::chooseGift($request->getPost('id_gift'));
                        break;
                    default:
                        break;
                }
            }
            if(!$this->arResult['BASKET'])
                self::getCart(true);
            if(!$this->arResult['ADDRESS'])
                self::getAddress();
            self::getOrder();
            self::getDelivery();
            self::getCoupon();
            self::initDigift();
            self::getContact();
        }

        $this->includeComponentTemplate($this->componentPage);
    }
}