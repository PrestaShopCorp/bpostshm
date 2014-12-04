<?php

class PsOrderBpostLabel extends ObjectModel
{

	/** @var integer */
	public $id_order_bpost_label;

	/** @var integer Bpost order id */
	public $id_order_bpost;

	/** @var boolean True if retour label */
	public $is_retour = 0;

	/** @var boolean True if Auto Retour is on */
	public $has_retour = 0;

	/** @var string Bpost Order Box / Label status */
	public $status;

	/** @var string bpost label barcode */
	public $barcode;

	/** @var string bpost label barcode if has_retour is True */
	public $barcode_retour;

	/** @var string Object creation date */
	public $date_add;

	/** @var string Object last modification date */
	public $date_upd;

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition;

	/**
	 * @see 1.4 ObjectModel->$table
	 *      	ObjectModel->$identifier
	 * @see 1.4 ObjectModel->$fieldsRequired
	 *      	ObjectModel->$fieldsValidate
	 */
	protected $table = 'order_bpost_label';
	protected $identifier = 'id_order_bpost_label';
	protected $fieldsRequired = array('id_order_bpost', 'is_retour', 'has_retour', 'status');
	protected $fieldsValidate = array(
		'id_order_bpost' =>	'isUnsignedId',
		'is_retour' =>		'isBool',
		'has_retour' =>		'isBool',
		'status' =>			'isString',
		);


	public function getFields()
	{
		parent::validateFields();

		$fields['id_order_bpost'] = 	(int)$this->id_order_bpost;
		$fields['is_retour'] = 			(int)$this->is_retour;
		$fields['has_retour'] = 		(int)$this->has_retour;
		$fields['status'] = 			pSQL($this->status);
		$fields['barcode'] = 			pSQL($this->barcode);
		$fields['barcode_retour'] = 	pSQL($this->barcode_retour);
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
				'table' => 'order_bpost_label',
				'primary' => 'id_order_bpost_label',
				'fields' => array(
					'id_order_bpost' =>		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
					'is_retour' =>			array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
					'has_retour' =>			array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
					'status' =>				array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
					'barcode' =>			array('type' => self::TYPE_STRING),
					'barcode_retour' =>		array('type' => self::TYPE_STRING),
					'date_add' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
					'date_upd' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
					),
				);
		}

		parent::__construct($id, $id_lang);
	}

	/**
	 * @see  ObjectModel::$webserviceParameters
	 */
	protected $webserviceParameters = array(
		'fields' => array(
			'id_order_bpost' => array('required' => true, 'xlink_resource'=> 'order_bpost'),
			),
	);

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

}