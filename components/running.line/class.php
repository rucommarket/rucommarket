<?php
use
Bitrix\Main\Loader,
Bitrix\Highloadblock as HL,
Bitrix\Main\Entity,
Bitrix\Main\Type\DateTime,
Bitrix\Main\Data\Cache;

class AuthenticaRunningLine extends CBitrixComponent
{
    private function getResult()
    {
        if(!$this->arParams['HLBLOCK_ID']) return;
        $hlbl = $this->arParams['HLBLOCK_ID'];
        $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch(); 

        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass(); 

        $date = new DateTime();

        $rsData = $entity_data_class::getList([
            "select" => ["*"],
            "order" => ["UF_SORT"=>"ASC","ID" => "DESC"],
            "filter" => [
                "UF_ACTIVE"=>true,
                [
                    "LOGIC" => "OR",
                    "UF_ACTIVE_FROM" => NULL,
                    "<=UF_ACTIVE_FROM"=> $date
                ],
                [
                    "LOGIC" => "OR",
                    "UF_ACTIVE_TO" => NULL,
                    ">=UF_ACTIVE_TO"=> $date
                ]
                ],
                'limit' => $this->arParams['ITEMS_LIMIT']
        ]);

        $this->arResult['ITEMS'] = [];
        while($arData = $rsData->Fetch()){
           $this->arResult['ITEMS'][] = $arData;
        }
    }

    private function upResult()
    {
        $date = new DateTime();
        $date = $date->getTimestamp();
        foreach($this->arResult['ITEMS'] as $key=>$item) {
            if(
                (
                    !empty($item['UF_ACTIVE_FROM']) &&
                    $item['UF_ACTIVE_FROM']->getTimestamp() > $date
                ) ||
                (
                    !empty($item['UF_ACTIVE_TO']) &&
                    $item['UF_ACTIVE_TO']->getTimestamp() < $date
                )
            ) {
                unset($this->arResult['ITEMS'][$key]);
                $this->ClearResultCache();
            }
        }
    }

    public function executeComponent()
    {
        if(!Loader::includeModule("highloadblock")) return;

        if ($this->StartResultCache()) {
            $this->getResult();
            $this->upResult();
            $this->includeComponentTemplate();
        } else {
            $this->upResult();
            $this->includeComponentTemplate();
        }
    }
}