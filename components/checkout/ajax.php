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
        $discounts = Sale\Discount::buildFromBasket($basket, $context);
        $dData = $discounts->calculate()->getData();
        if (isset($dData['BASKET_ITEMS']))
            $basket->applyDiscount($dData['BASKET_ITEMS']);
        $basketItems = $basket->getBasketItems();
        foreach($basketItems as $item):
            if($item->getId() == $id) {
                $item->setField('QUANTITY', $quantity);
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
}