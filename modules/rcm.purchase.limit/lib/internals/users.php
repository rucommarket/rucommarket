<?php
namespace RCM\Purchase\Limit\Internals;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
  
class UsersTable extends Entity\DataManager
{
   /**
    * Returns DB table name for entity.
    *
    * @return string
    */
   public static function getTableName()
   {
      return 'rcm_purchase_limit_users';
   }
 
   /**
    * Returns entity map definition.
    *
    * @return array
    */
   public static function getMap()
   {
      return array(
        new Entity\IntegerField('ID', array(
            'primary' => true,
            'autocomplete' => true
        )),
        new Entity\BooleanField('ACTIVE', array(
            'values' => array('N', 'Y'),
            'default_value' => 'Y'
        )),
        new Entity\DatetimeField('DATE_CREATE', array(
            'default_value' => function()
                {
                    return new Main\Type\DateTime();
                }
        )),
        new Entity\IntegerField('CREATED_BY'),
        new Entity\DatetimeField('TIMESTAMP_X', array(
            'required' => true,
            'default_value' => function()
                {
                    return new Main\Type\DateTime();
                }
        )),
        new Main\Entity\IntegerField('MODIFIED_BY'),
        new Main\Entity\IntegerField('USER_ID', array(
            'required' => true,
            'validation' => array(__CLASS__, 'validateUserId')
        )),
        new Main\Entity\IntegerField('LIMIT', array(
            'default_value' => null
        )),
        new Main\Entity\ReferenceField(
            'USER',
            '\Bitrix\Main\User',
            array('=this.USER_ID' => 'ref.ID')
        ),
        new Main\Entity\ReferenceField(
            'CREATED_BY_USER',
            '\Bitrix\Main\User',
            array('=this.CREATED_BY' => 'ref.ID')
        ),
        new Main\Entity\ReferenceField(
            'MODIFIED_BY_USER',
            '\Bitrix\Main\User',
            array('=this.MODIFIED_BY' => 'ref.ID')
        ),
      );
   }

   /**
	 * Returns validators for UserId field.
	 *
	 * @return array
	 */
	public static function validateUserId()
	{
		return array(
			new Entity\Validator\Length(null, 32),
            new Entity\Validator\Unique()
		);
    }
}