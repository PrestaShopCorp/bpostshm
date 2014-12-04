<?php

class PsCartBpost extends ObjectModel
{

	/** @var integer */
	public $id_cart_bpost;

	/** @var integer ps_cart id */
	public $id_cart;

	/* int service point choice id */
	public $service_point_id = 0;

	/* int service point type @bpost(1 or 2) @247(4) */
	public $sp_type = 0;

	/* int keep me informed choice value (default 0 => email) */
	public $option_kmi = 0;

	/* json encoded parcel locker customer info */
	public $bpack247_customer;

	/* may not need dates */
	/** @var string Object creation date */
	public $date_add;

	/** @var string Object last modification date */
	public $date_upd;

	/**
	 * @see 1.5+ ObjectModel::$definition
	 */
	public static $definition;

	/**
	 * @see 1.4 ObjectModel->$table
	 *      	ObjectModel->$identifier
	 * @see 1.4 ObjectModel->$fieldsRequired
	 *      	ObjectModel->$fieldsValidate
	 */
	protected $table = 'cart_bpost';
	protected $identifier = 'id_cart_bpost';
	protected $fieldsRequired = array('id_cart');
	protected $fieldsValidate = array(
		'id_cart' => 			'isUnsignedId',
		'service_point_id' =>	'isUnsignedId',
		'sp_type' =>			'isUnsignedId',
		'option_kmi' =>			'isUnsignedId',
		'bpack247_customer' =>	'isString',
		);

	/**
	 * @see  ObjectModel::$webserviceParameters
	 */
	protected $webserviceParameters = array(
		'fields' => array(
			'id_cart' => array('required' => true, 'xlink_resource'=> 'cart'),
			),
	);

	/* 1.4 db save required */
	public function getFields()
	{
		parent::validateFields();

		$fields['id_cart'] = 			(int)$this->id_cart;
		$fields['service_point_id'] =	(int)$this->service_point_id;
		$fields['sp_type'] = 			(int)$this->sp_type;
		$fields['option_kmi'] = 		(int)$this->option_kmi;
		$fields['bpack247_customer'] = 	pSQL($this->bpack247_customer);
		$fields['date_add'] = 			pSQL($this->date_add);
		$fields['date_upd'] = 			pSQL($this->date_upd);

		return $fields;
	}

	public function __construct($id = null, $id_lang = null)
	{
		// 1.4 is retarded to the max
		if (version_compare(_PS_VERSION_, '1.5', '>='))
		{
			self::$definition = array(
				'table' => 'cart_bpost',
				'primary' => 'id_cart_bpost',
				'fields' => array(
					'id_cart' =>					array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
					'service_point_id' =>			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
					'sp_type' =>					array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
					'option_kmi' =>					array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
					'bpack247_customer' =>			array('type' => self::TYPE_STRING, 'validate' => 'isString'),
					'date_add' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
					'date_upd' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
					),
				);
		}

		parent::__construct($id, $id_lang);
	}

	/**
	 * Save current object to database (add or update)
	 *
	 * @param bool $null_values
	 * @param bool $autodate
	 * @return boolean Insertion result
	 */
	public function save($null_values = true, $autodate = true)
	{
		return parent::save($null_values, $autodate);
	}

	public function update($null_values = true)
	{
		return parent::update($null_values);
	}

	/**
	 * Get prestashop order using reference
	 * 
	 * @param int $ps_cart_id
	 * @return PsCartBpost Order
	 */
	public static function getByPsCartID($ps_cart_id)
	{
		$result = Db::getInstance()->getRow('
		SELECT `id_cart_bpost`
		FROM `'._DB_PREFIX_.'cart_bpost`
		WHERE id_cart = '.(int)$ps_cart_id);

		if (isset($result['id_cart_bpost']))
			return new PsCartBpost((int)$result['id_cart_bpost']);

		$cart_bpost = new PsCartBpost();
		$cart_bpost->id_cart = (int)$ps_cart_id;
		$cart_bpost->save();

		return $cart_bpost;
	}

	public function reset()
	{
		return $this->setServicePoint(0, 0);
	}

	public function setServicePoint($id = 0, $type = 0)
	{
		if (!is_numeric($id) || !is_numeric($type))
			return false;

		$this->service_point_id = $id;
		$this->sp_type = $type;
		return $this->update();
	}

	public function validServicePointForSHM($shipping_method = 0)
	{
		$valid = false;
		switch ((int)$shipping_method)
		{
			case 1: // @home
				$valid = true; //empty($this->service_point_id);
				break;

			case 2: // @bpost
				$valid = in_array($this->sp_type, array(1, 2));
				break;

			case 4: // @24/7
				$valid = $shipping_method == $this->sp_type;
				break;
		}

		return $valid;
	}
}