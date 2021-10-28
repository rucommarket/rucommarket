<?php
namespace AC\Api\Internals;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
  
class RequestsTable extends Entity\DataManager
{
   /**
    * Returns DB table name for entity.
    *
    * @return string
    */
   public static function getTableName()
   {
      return 'ac_api_requests';
   }
 
   /**
    * Returns entity map definition.
    *
    * @return array
    */
    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true
            ]),
            new Entity\IntegerField('PROJECT_ID', [
                'required' => true,
            ]),
            new Entity\DatetimeField('DATE_CREATE', [
                'default_value' => function()
                {
                    return new Main\Type\DateTime();
                }
            ]),
            new Entity\TextField('REQUEST', [
                'default_value' => null
            ]),
        );
    }
}