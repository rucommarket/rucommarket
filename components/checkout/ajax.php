<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use \Bitrix\Main;
use \Bitrix\Sale;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Error;
use \Bitrix\Main\Engine\Controller;
use \Bitrix\Main\Application;
use \Bitrix\Main\Web\Json;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Data\Cache;


class CheckoutAjaxController extends Controller
{
    public function configureActions()
    {
        return [
            'editQuantityCart' => [
                'prefilters' => []
            ],
            'deleteCart' => [
                'prefilters' => []
            ],
            'editFormContact' => [
                'prefilters' => []
            ],
            'editFormAddressComment' => [
                'prefilters' => []
            ],
        ];
    }

    public function editQuantityCartAction($id,$quantity)
    {
        if(!Loader::includeModule('sale')) $this->addError(new Error('Ошибка модуля', '404'));
        if(!check_bitrix_sessid()) $this->addError(new Error('Ошибка доступа', '403'));

        if($quantity<1) {
            $this->addError(new Error('Неверно указано количество', '400'));
        }
        if($id<1) {
            $this->addError(new Error('Неверно указана корзина', '400'));
        }

        global $USER;
        if(!is_object($USER)) $USER = new \CUser();
        
        if(!empty($this->getErrors())) return NULL;

        $result = [];

        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
        $context = new Sale\Discount\Context\Fuser($basket->getFUserId());
        $basketItems = $basket->getBasketItems();
        foreach($basketItems as $item):
            if($item->getId() == $id) {
                $item->setField('QUANTITY', $quantity);
                $discounts = Sale\Discount::buildFromBasket($basket, $context);
                $dData = $discounts->calculate()->getData();
                if (isset($dData['BASKET_ITEMS']))
                    $basket->applyDiscount($dData['BASKET_ITEMS']);
                $result['BASKET_ITEM'] = [
                    'ID' => $item->getId(),
                    'PRODUCT_ID' => $item->getProductId(),
                    'PRICE' => number_format($item->getPrice(),2,'.',' '),
                    'BASE_PRICE' => number_format($item->getBasePrice(),2,'.',' '),
                    'QUANTITY' => $item->getQuantity(),
                    'FINAL_PRICE' => number_format($item->getFinalPrice(),2,'.',' '),
                ];
                $item->save();
            }
            $result['BASKET_ITEMS'][$item->getId()] = [
                'ID' => $item->getId(),
                'PRODUCT_ID' => $item->getProductId(),
                'PRICE' => number_format($item->getPrice(),2,'.',' '),
                'BASE_PRICE' => number_format($item->getBasePrice(),2,'.',' '),
                'QUANTITY' => $item->getQuantity(),
                'FINAL_PRICE' => number_format($item->getFinalPrice(),2,'.',' '),
            ];
        endforeach;
        $result['BASKET']['PRICE'] = number_format($basket->getPrice(),2,'.',' ');
        $result['BASKET']['FULL_PRICE'] = number_format($basket->getBasePrice(),2,'.',' ');
        $result['BASKET']['DISCOUNT_PRICE'] = '-'.number_format($basket->getBasePrice()-$basket->getPrice(),2,'.',' ');
        return (empty($this->getErrors()))?$result:null;
    }

    public function deleteCartAction($id)
    {
        if(!Loader::includeModule('sale')) $this->addError(new Error('Ошибка модуля', '404'));
        if(!check_bitrix_sessid()) $this->addError(new Error('Ошибка доступа', '403'));

        if($id<1) {
            $this->addError(new Error('Неверно указана корзина', '400'));
        }

        global $USER;
        if(!is_object($USER)) $USER = new \CUser();
        
        if(!empty($this->getErrors())) return NULL;

        $result = [];

        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
        $context = new Sale\Discount\Context\Fuser($basket->getFUserId());
        $discounts = Sale\Discount::buildFromBasket($basket, $context);
        $dData = $discounts->calculate()->getData();
        if (isset($dData['BASKET_ITEMS']))
            $basket->applyDiscount($dData['BASKET_ITEMS']);
        $basketItems = $basket->getBasketItems();
        $basket->getItemById($id)->delete();
        if (isset($dData['BASKET_ITEMS']))
            $basket->applyDiscount($dData['BASKET_ITEMS']);
        $basket->save();
        $result['BASKET']['PRICE'] = number_format($basket->getPrice(),2,'.',' ');
        return (empty($this->getErrors()))?$result:null;
    }

    public function editFormContactAction($form)
    {
        $cache = Cache::createInstance();
        $arContact = [
            'PHONE' => $form['PHONE'],
            'EMAIL' => $form['EMAIL'],
            'NAME' => $form['NAME'],
            'LAST_NAME' => $form['LAST_NAME'],
            'SECOND_NAME' => $form['SECOND_NAME']
        ];
        $cache->clean("contact_".Sale\Fuser::getId(),'order_contact');
        if ($cache->startDataCache(7200, "contact_".Sale\Fuser::getId(),'order_contact')) {
            $cache->endDataCache($arContact);
        }
        //if(!empty($this->getErrors())) return NULL;
        return $arContact;
    }

    public function editFormAddressCommentAction($comment)
    {
        $cache = Cache::createInstance();
        if($cache->initCache(7200, "address_".Sale\Fuser::getId(),'addresses')) {
            $address = $cache->getVars();
        } else {
            $address = [];
        }
        $address['COMMENT'] = $comment;
        $cache->clean("address_".Sale\Fuser::getId(),'addresses');
        if ($cache->startDataCache(3600, "address_".Sale\Fuser::getId(),'addresses')) {
            $cache->endDataCache($address);
        }
    }
}