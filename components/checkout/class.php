<?php
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Bitrix\Conversion\Internals\MobileDetect;
use \Bitrix\Main\Config\Option;
use \Bitrix\Sale;
use \Bitrix\Sale\Delivery;
use \Bitrix\Sale\PaySystem;
use \Bitrix\Main\Data\Cache;

class Checkout extends \CBitrixComponent
{
    protected $componentPage = '';
    protected $basketStorage;

    private $order = [];
    private $shipment = [];

    private $deliveryChecked = 0;

    private function sefMode()
    {
        $arDefaultUrlTemplates404 = array(
            "cart" => "index.php",
            "order" => "order/"
        );
        $arDefaultVariableAliases404 = array();
        $arDefaultVariableAliases = array();
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
        $basketItems = $basket->getBasketItems();
        $basket->getItemById($id)->delete();
        $basket->save();
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

    private function getCart()
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

        if (!isset($this->basketStorage))
		{
			$this->basketStorage = Sale\Basket\Storage::getInstance(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
		}
        $basketStorage = $this->basketStorage;
        $basket = $basketStorage->getBasket();
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

        foreach($basketItems as $item):
            $basketPropertyCollection = $item->getPropertyCollection();
            if($basketPropertyCollection->getPropertyValues()['IS_GIFT']['VALUE'] == 'Y' && $item->getFinalPrice() > 0) {
                $item->delete();
                $basket->save();
                continue;
            }
        endforeach;

        $basketItems = $basket->getBasketItems();

        $arBasket = &$this->arResult['BASKET'];
        $arBasket = [
            'PRICE' => Sale\PriceMaths::roundPrecision($orderableBasket->getPrice()),
            'FULL_PRICE' => Sale\PriceMaths::roundPrecision($orderableBasket->getBasePrice()),
            'WEIGHT' => $orderableBasket->getWeight()
        ];
        foreach($basketItems as $item):
            if($item->canBuy() && !$item->isDelay())
                $item = $orderableBasket->getItemByBasketCode($item->getBasketCode());
            $arBasketItem = $item->getFieldValues();
            $arBasket['ITEMS'][$item->getId()] = [
                'ID' => $item->getId(),
                'PRODUCT_ID' => $item->getProductId(),
                'PRICE' => Sale\PriceMaths::roundPrecision($item->getPrice()),
                'BASE_PRICE' => Sale\PriceMaths::roundPrecision($item->getBasePrice()),
                'QUANTITY' => $item->getQuantity(),
                'FINAL_PRICE' => Sale\PriceMaths::roundPrecision($item->getFinalPrice()),
                'WEIGHT' => $item->getWeight(),
                'CAN_BUY' => $item->canBuy(),
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
            $arBasket['ITEMS'][$item->getId()]['PRODUCT']['PICTURE'] = \CFile::ResizeImageGet(
                $arBasket['ITEMS'][$item->getId()]['PRODUCT']['ELEMENT_PREVIEW_PICTURE'],
                ["width"=>50,"height"=>50],
                BX_RESIZE_IMAGE_PROPORTIONAL
            )['src'];
            $arBasket['ITEMS'][$item->getId()]['PRODUCT']['URL'] = 
                \CIBlock::ReplaceDetailUrl( $arBasket['ITEMS'][$item->getId()]['PRODUCT']['DETAIL_PAGE_URL'], $arBasket['ITEMS'][$item->getId()]['PRODUCT'],false,'E');
        endforeach;
    }

    private function getAddress()
    {
        global $USER;
        if(!is_object($USER)) $USER = new \CUser();
        if(!Loader::includeModule('sale')) return false;
        $arResult = &$this->arResult;
        $order = &$this->order;
        $cache = Cache::createInstance();
        if($cache->initCache(3600, "address_".Sale\Fuser::getId(),'addresses')) {
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
        $order = &$this->order;
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
        if ($cache->startDataCache(3600, "address_".Sale\Fuser::getId(),'addresses')) {
            $cache->endDataCache($arResult['ADDRESS']); // записываем в кеш
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
        $arMulti = [0=>false,1=>false,'CHECKED'=>-1];
        $services = Sale\Delivery\Services\Manager::getRestrictedObjectsList($shipment);
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
                    $arDelivery['DATA'] = $calcResult->getTmpData();
                    $arMulti[$arDelivery['DATA']['MULTIVARIANT']] = true;
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
					$arDelivery['CALCULATE_ERRORS'] = Loc::getMessage('SOA_DELIVERY_CALCULATE_ERROR');
				}
			}

            $arDelivery['CALCULATE_DESCRIPTION'] = $calcResult->getDescription();
            if($calcResult->isSuccess())
                $arDeliveries[$arDelivery['ID']] = $arDelivery;
            unset($arDelivery);
        }
    }

    private function checkDelivery($deliveryID = '')
    {
        $_SESSION['BX_DELIVERY_ID_CHECKED'] = $deliveryID;
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
        if ($cache->startDataCache(3600, "contact_".Sale\Fuser::getId(),'order_contact')) {
            $cache->endDataCache($arContact); // записываем в кеш
        }
        if(empty($request->getPost('PHONE')))
            return new \Bitrix\Main\Error("Отсутствует номер телефона",1);
        if(empty($request->getPost('EMAIL')))
            return new \Bitrix\Main\Error("Отсутствует E-mail",2);
        if(empty($request->getPost('NAME')))
            return new \Bitrix\Main\Error("Отсутствует Имя",3);
        if(empty($request->getPost('LAST_NAME')))
            return new \Bitrix\Main\Error("Отсутствует Фамилия",4);
        return true;
    }

    private function getContact()
    {
        $arContact = &$this->arResult['CONTACT'];
        $cache = Cache::createInstance();
        if($cache->initCache(3600, "contact_".Sale\Fuser::getId(),'order_contact')) {
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
            $userID = \CSaleUser::GetAnonymousUserID();
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
            'PAYMENT_SYSTEM_ID' => $order->getPaymentSystemId(),
            'DELIVERY_ID' => $order->getDeliverySystemId(),
            'DELIVERY_PRICE' => Sale\PriceMaths::roundPrecision($order->getDeliveryPrice()),
            'PERSON_TYPE_ID' => $order->getPersonTypeId()
        ];

        $order->doFinalAction(true);
    }

    private function addCoupon($coupon)
    {
        if($arCoupon = \Bitrix\Sale\DiscountCouponsManager::isExist($coupon)) {
            return \Bitrix\Sale\DiscountCouponsManager::add($coupon);
        } else {
            return false;
        }
    }

    private function removeCoupon($coupon)
    {
        return \Bitrix\Sale\DiscountCouponsManager::delete(trim($coupon));
    }

    private function saveOrder()
    {
        if(!Loader::includeModule('sale')) return false;

        self::getCart();
        self::getAddress();
        $order = &$this->order;
        $arResult = &$this->arResult;
        $arAddress = $arResult['ADDRESS'];
        $propertyCollection = $order->getPropertyCollection();
        $arProperties = [
            'PHONE' => $arResult['CONTACT']['PHONE'],
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
            'ADDRESS_COMMENT' => $arAddress['COMMENT']
        ];

        foreach($propertyCollection as $prop) {
            $code = $prop->getProperty()['CODE'];
            if(isset($arProperties[$code])) {
                $prop->setValue($arProperties[$code]);
            }
        }

        $order->save();
    }

    public function executeComponent()
    {
        if(!Loader::includeModule('sale')) return false;

        global $USER;
        if(!is_object($USER)) $USER = new \CUser();
        $request = Application::getInstance()->getContext()->getRequest();

        self::sefMode();
        if($this->componentPage == 'payment') {
            if(!isset($_REQUEST['ORDER_ID'])) {
                $resultContact = self::setContact();
                if($resultContact === true) {
                    self::getCart();
                    self::getOrder();
                    self::saveOrder();
                    $orderId = $this->order->getId();
                    $_REQUEST['ORDER_ID'] = $orderId;
                } elseif(is_object($resultContact) && method_exists($resultContact,'getCode') && $resultContact->getCode()>0) {
                    $this->arResult['CONTACT']['ERROR'] = $resultContact->jsonSerialize();
                    $this->componentPage = 'order';
                }
            }
        }
        if($this->componentPage == 'cart') {
            if($request->getPost('AJAX_CALL') == 'Y') {
                switch ($request->getPost('action')) {
                    case 'delete_basket':
                        self::deleteBasket($request->getPost('id_basket'));
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
                    case 'add_coupon':
                        self::addCoupon($request->getPost('coupon'));
                        break;
                    case 'remove_coupon':
                        self::removeCoupon($request->getPost('coupon'));
                        break;
                    case 'check_address':
                        self::checkAddress();
                        break;
                    case 'check_delivery':
                        self::checkDelivery($request->getPost('check_delivery'));
                        break;
                    case 'check_payment':
                        self::checkPayment($request->getPost('check_payment'));
                        break;
                    case 'delete_basket':
                        self::deleteBasket($request->getPost('id_basket'));
                        break;
                    default:
                        break;
                }
            }
            self::getCart();
            self::getAddress();
            self::getOrder();
            self::getDelivery();
            self::getCoupon();
            self::getContact();
        }


        $this->includeComponentTemplate($this->componentPage);
    }
}