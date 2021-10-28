<?php
namespace AC\Api\Internals;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
  
class ProjectsTable extends Entity\DataManager
{
   /**
    * Returns DB table name for entity.
    *
    * @return string
    */
   public static function getTableName()
   {
      return 'ac_api_projects';
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
        new Entity\StringField('NAME', array(
            'required' => true,
            'validation' => array(__CLASS__, 'validateName')
        )),
        new Entity\StringField('CODE', array(
            'required' => true,
            'validation' => array(__CLASS__, 'validateCode')
        )),
        new Entity\StringField('LOGIN', array(
            'required' => true,
            'validation' => array(__CLASS__, 'validateLogin')
        )),
        new Entity\StringField('PASSWORD', array(
            'required' => true,
            'validation' => array(__CLASS__, 'validatePassword')
        )),
        new Entity\StringField('IP_ADDRESS', array(
            'required' => true,
            'validation' => array(__CLASS__, 'validateIpAddress')
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
        new Entity\IntegerField('MODIFIED_BY'),
        new Entity\ReferenceField(
            'CREATED_BY_USER',
            '\Bitrix\Main\User',
            array('=this.CREATED_BY' => 'ref.ID')
        ),
        new Entity\ReferenceField(
            'MODIFIED_BY_USER',
            '\Bitrix\Main\User',
            array('=this.MODIFIED_BY' => 'ref.ID')
        ),
      );
   }
   /**
	 * Returns validators for Name field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 32),
            new Entity\Validator\Unique()
		);
    }
    /**
	 * Returns validators for Code field.
	 *
	 * @return array
	 */
	public static function validateCode()
	{
		return array(
			new Entity\Validator\Length(null, 32),
            new Entity\Validator\Unique()
		);
    }
    /**
	 * Returns validators for Login field.
	 *
	 * @return array
	 */
	public static function validateLogin()
	{
		return array(
			new Entity\Validator\Length(null, 32),
            new Entity\Validator\Unique()
		);
    }
    /**
	 * Returns validators for Password field.
	 *
	 * @return array
	 */
	public static function validatePassword()
	{
		return array(
			new Entity\Validator\Length(null, 32),
		);
    }
    /**
	 * Returns validators for IP address field.
	 *
	 * @return array
	 */
	public static function validateIpAddress()
	{
		return array(
			new Entity\Validator\Length(7, 15),
            array(__CLASS__, 'checkIpAddress')
		);
    }

    /**
	 * Проверка ip адреса.
	 *
	 * @param string $value					ip address.
	 * @param array|int $primary			Primary key.
	 * @param array $row					Current data.
	 * @param Main\Entity\Field $field		Field object.
	 * @return bool|string
	 */
    public static function checkIpAddress($value, $primary, array $row, Main\Entity\Field $field)
    {
        if(filter_var($value, FILTER_VALIDATE_IP)) return true;
        return Loc::getMessage('AC_API_PROJECTS_ENTITY_VALIDATOR_IP_ADDRESS');
    }
}