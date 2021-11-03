<?
namespace RCM\Purchase\Limit;
 
use Bitrix\Main\Config\Option;
use Bitrix\Sale;

class Handlers {

    private function getLimitMonth($userID) {
        global $USER;
        if(!is_object($USER)){
            $USER = new CUser();
        }
        \Bitrix\Main\Loader::includeModule("sale");

        $discounts = Option::get('rcm.purchase.limit','DISCOUNTS');
        $arDiscounts = explode(",",$discounts);
        $arGroupsPrice = explode(",",Option::get('rcm.purchase.limit','GROUPS_PRICE'));

        $dbBaskets = \Bitrix\Sale\Internals\BasketTable::getList(array(
            'order' => array('ID' => 'desc'),
            'filter' => array(
                'ORDER.PAYED' => 'Y',
                'ORDER.USER_ID' => $userID,
                '>=ORDER.DATE_INSERT' => '01.'.date('m.Y').' 00:00:00',
                'PRICE_TYPE_ID'=> $arGroupsPrice
            ),
            'select' => [
                'ID',
                'ORDER_ID',
                'PRICE',
                'QUANTITY',
                'DISCOUNT_ID' => 'ORDER_DISCOUNT.DISCOUNT_ID'
            ],
            'runtime' => [
                'ORDER_RULES' => [
                    'data_type' => '\Bitrix\Sale\Internals\OrderRulesTable',
                    'reference' => ['=this.ORDER.ID' => 'ref.ORDER_ID',
                                    '=this.ID' => 'ref.ENTITY_ID'],
                    'join_type' => 'left'
                ],
                'ORDER_DISCOUNT' => [
                    'data_type' => '\Bitrix\Sale\Internals\OrderDiscountTable',
                    'reference' => ['=this.ORDER_RULES.ORDER_DISCOUNT_ID' => 'ref.ID'],
                    'join_type' => 'left'
                ],
            ]
        ));
        
        $limitOrder = 0;
        while($arBasket = $dbBaskets->Fetch()) {
            $limitOrder += $arBasket['PRICE'] * $arBasket['QUANTITY'];
        }

        $limit = Option::get('rcm.purchase.limit','LIMIT');
        
        if($USER->getID() > 0) {
            $arUser = \RCM\Purchase\Limit\Internals\UsersTable::getList([
                'select' => ['LIMIT'],
                'filter' => ['USER_ID'=>$USER->getID(),'ACTIVE'=>'Y']
            ]);
            if($result = $arUser->Fetch()) $limit = $result['LIMIT'];
        }

        if(empty($limit)) return;

        $limit = $limit - $limitOrder;

        return $limit;
    }
    
    public function OnGetDiscountResult(&$arResult) {
        global $USER;
        if(!is_object($USER)){
            $USER = new CUser();
        }
        $active = Option::get('rcm.purchase.limit','ACTIVE');
        if($active != 'Y') return true;
        
        \Bitrix\Main\Loader::includeModule("sale");

        $groups = Option::get('rcm.purchase.limit','GROUP');
        $arGroups = explode(",",$groups);
        $userGroups = explode(",",$USER->getGroups());
        if(empty(array_intersect($arGroups,$userGroups))) return true;

        $discounts = Option::get('rcm.purchase.limit','DISCOUNTS');
        $arDiscounts = explode(",",$discounts);

        if(empty($arDiscounts)) return true;

        $limit = self::getLimitMonth($USER->getID());

        if(empty($limit)) return true;
        
        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());

        if($basket->getPrice() > $limit):
            foreach($arResult as $key=>$discount){
                if(in_array($discount['ID'],$arDiscounts)) unset($arResult[$key]);
            }
        endif;

        return true;
    }

    public function OnGetOptimalPrice($productID, $quantity = 1, $arUserGroups = array(), $renewal = "N", $arPrices = array(), $siteID = false, $arDiscountCoupons = false) {
        global $USER;
        global $getCountHand;
        if(!is_object($USER)){
            $USER = new CUser();
        }
        if(!empty($arPrices)) return true;
        \Bitrix\Main\Loader::includeModule("catalog");
        \Bitrix\Main\Loader::includeModule("sale");

        $limit = self::getLimitMonth($USER->getID());
        if(empty($limit)) return true;
        
        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());

        $groups = Option::get('rcm.purchase.limit','GROUP');
        $arGroupsPrice = explode(",",Option::get('rcm.purchase.limit','GROUPS_PRICE'));
        $arGroups = explode(",",$groups);
        $userGroups = explode(",",$USER->getGroups());
        if(empty(array_intersect($arGroups,$userGroups))) return true;

        $resOptPrices = \Bitrix\Catalog\PriceTable::getList([
            'filter' => ['=PRODUCT_ID' => $productID],
            'select' => ['ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY', 'PRICE_SCALE'],
        ]);
        $arPrices = [];
        while($optPrice = $resOptPrices->fetch()){
            if(!in_array($optPrice['CATALOG_GROUP_ID'],$arGroupsPrice)):
                $arPrices[] = $optPrice;
            elseif($basket->getPrice() < $limit):
                $arPrices[] = $optPrice;
            endif;
        }
        if(empty($arPrices)){
            foreach($basket as $bItem){
                if($bItem->getProductId() == $productID) {
                    $basket->getItemById($bItem->getID())->delete();
                    $basket->save();
                }
            }
            return true;
        }


        $arPrice = \CCatalogProduct::GetOptimalPrice($productID, $quantity, $USER->GetUserGroupArray(), $renewal, $arPrices, $siteID, $arDiscountCoupons);

        return $arPrice;
    }

    public function getLimit()
    {
        global $USER;
        if(!is_object($USER)){
            $USER = new CUser();
        }
        $groups = Option::get('rcm.purchase.limit','GROUP');
        $arGroups = explode(",",$groups);
        $userGroups = explode(",",$USER->getGroups());
        if(empty(array_intersect($arGroups,$userGroups))) return false;
        $limit = self::getLimitMonth($USER->getID());
        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
        if($limit > $basket->getPrice()) return true;
        return false;
    }
}