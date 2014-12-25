<?php

class PsOrderBpost extends ObjectModel
{

	public $id_shop_group;

	public $id_shop;

	/** @var integer Order State id */
	public $current_state = 0;

	/** @var string Actual Bpost order status */
	public $status;

	/** @var int shipping method (8+1) if international */
	public $shm = 0;

	/** @var string Displayed delivery method in delivery options */
	public $delivery_method;

	/** @var string Displayed recipient */
	public $recipient;

	/** @var string Object creation date */
	public $date_add;

	/** @var string Object last modification date */
	public $date_upd;

	/**
	 * @var string Bpost Order reference, should be unique
	 */
	public $reference;

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
	protected $table = 'order_bpost';
	protected $identifier = 'id_order_bpost';
	protected $fieldsRequired = array('reference', 'current_state', 'shm', 'delivery_method', 'recipient');
	protected $fieldsValidate = array(
		'reference' =>			'isString',
		'current_state' =>		'isUnsignedId',
		'shm' =>				'isUnsignedId',
		'delivery_method' =>	'isString',
		'recipient' =>			'isString',
		);


	public function getFields()
	{
		parent::validateFields();

		$fields['reference'] = 			pSQL($this->reference);
		$fields['current_state'] = 		(int)$this->current_state;
		$fields['status'] = 			pSQL($this->status);
		$fields['shm'] =				(int)$this->shm;
		$fields['delivery_method'] = 	pSQL($this->delivery_method);
		$fields['recipient'] = 			pSQL($this->recipient);
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
				'table' => 'order_bpost',
				'primary' => 'id_order_bpost',
				'multishop' => true,
				'fields' => array(
					'reference' =>			array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
					'id_shop_group' =>		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
					'id_shop' =>			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
					'current_state' =>		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
					'status' =>				array('type' => self::TYPE_STRING),
					'shm' =>				array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
					'delivery_method' =>	array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
					'recipient' =>			array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
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
		if ((int)$this->id > 0)
			return parent::update($null_values);

		$return = parent::add($null_values, $autodate);

		// must manually set Prestashop 1.5+
		// id_shop, id_shop_group to take hold !
		if (self::isPs15Plus())
		{
			// Context is not dependable ! Only ps_orders values are safe.
			$id_order = (int)Tools::substr($this->reference, 7);
			$sql = 'UPDATE `'._DB_PREFIX_.self::$definition['table'].'` ob, `'._DB_PREFIX_.'orders` o
			SET ob.`id_shop` = o.`id_shop`,
				ob.`id_shop_group` = o.`id_shop_group`
			WHERE ob.`id_order_bpost` = '.(int)$this->id.'
			AND o.`id_order` = '.(int)$id_order;

			$return = $return && Db::getInstance()->execute($sql);
		}

		return $return;
	}

	/**
	 * Get bpost order using reference
	 * 
	 * @param string $reference
	 * @return PsOrderBpost
	 */
	public static function getByReference($reference)
	{
		$result = Db::getInstance()->getRow('
		SELECT `id_order_bpost`
		FROM `'._DB_PREFIX_.'order_bpost`
		WHERE `reference` = "'.(string)$reference.'"');

		return isset($result['id_order_bpost']) ? new PsOrderBpost((int)$result['id_order_bpost']) : false;
	}

	/**
	 * Get bpost order using Prestashop order id
	 * 
	 * @param int $ps_order_id
	 * @return PsOrderBpost if found false otherwise
	 */
	public static function getByPsOrderID($ps_order_id)
	{
		$result = Db::getInstance()->getRow('
		SELECT `id_order_bpost`
		FROM `'._DB_PREFIX_.'order_bpost`
		WHERE SUBSTRING(`reference`, 8) = '.(int)$ps_order_id);

		return isset($result['id_order_bpost']) ? new PsOrderBpost((int)$result['id_order_bpost']) : false;
	}

	/**
	 * Get prestashop order using reference
	 * 
	 * @param string $reference
	 * @return Prestashop Order
	 */
	public static function getPsOrderByReference($reference)
	{
		return new Order((int)Tools::substr($reference, 7));
	}

	/**
	 * [isPs15Plus helper static function
	 * @return boolean True if Prestashop is 1.5+
	 */
	private static function isPs15Plus()
	{
		return (bool)version_compare(_PS_VERSION_, '1.5', '>=');
	}

	/**
	 * add bpost label using reference, is_retour
	 * 
	 * @param bool 	$is_retour
	 * @return bool
	 */
	public function addLabel($is_retour = false, $status = 'PENDING')
	{
		if (!(bool)$this->id)
			return false;

		// Configuration context is not reliable
		// if (self::isPs15Plus())
		// 	Shop::setContext(Shop::CONTEXT_SHOP, (int)$this->id_shop);

		$auto_retour = (bool)Configuration::get('BPOST_AUTO_RETOUR_LABEL');

		$order_label = new PsOrderBpostLabel();
		$order_label->id_order_bpost = $this->id;
		$order_label->is_retour = (bool)$is_retour;
		$order_label->has_retour = (bool)$auto_retour;
		$order_label->status = (string)$status;
		return $order_label->save();
	}

	public function countPrinted()
	{
		$result = Db::getInstance()->getRow('
		SELECT COUNT(`id_order_bpost_label`) AS count_printed
		FROM `'._DB_PREFIX_.'order_bpost_label`
		WHERE `id_order_bpost` = '.$this->id.'
		AND barcode IS NOT NULL');

		return isset($result['count_printed']) ? (int)$result['count_printed'] : false;
	}

	/**
	 * Get Bpost order labels using id
	 * 
	 * @return Collection of PsOrderBpostLabel
	 */
	public function getLabels()
	{
		$order_labels = new Collection('PsOrderBpostLabel');
		$order_labels->where('id_order_bpost', '=', $this->id);
		return $order_labels->getResults();
	}

	/**
	 * Get new Bpost order labels using id
	 * 
	 * @param bool $separate if true into [1] => has_retour and [0] => hasn't
	 * @return array of PsOrderBpostLabel Collections
	 */
	public function getNewLabels($separate = true)
	{
		$new_labels = array();

		$rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
		SELECT `id_order_bpost_label`, `has_retour`
		FROM `'._DB_PREFIX_.'order_bpost_label`
		WHERE `id_order_bpost` = '.$this->id.' AND barcode IS NULL
		ORDER BY id_order_bpost_label ASC');

		if ($rows)
			foreach ($rows as $row)
			{
				$order_bpost_label = new PsOrderBpostLabel((int)$row['id_order_bpost_label']);
				if ($separate)
					$new_labels[(int)$row['has_retour']][] = $order_bpost_label;
				else
					$new_labels[] = $order_bpost_label;

			}

		// $order_labels = new Collection('PsOrderBpostLabel');
		// $order_labels->sqlWhere('id_order_bpost = '.$this->id.' AND barcode IS NULL');
		// // $order_labels->orderBy('is_retour', 'ASC');
		// $order_labels->sqlOrderBy('id_order_bpost_label ASC, is_retour ASC');
		// $all_labels = $order_labels->getResults();
		// if ($separate)
		// 	foreach ($all_labels as $label)
		// 		$new_labels[(int)$label->has_retour][] = $label;
		// else
		// 	$new_labels = $all_labels;

		return $new_labels;
	}

	public function getAllNewLabels()
	{
		$new_labels = array();

		$is_intl = (bool)($this->shm > 7);
		if ($rows = $this->_getAllEmptyLabels())
		{
			$labels_regular = array();
			$labels_odd = array();
			foreach ($rows as $row)
			{
				$order_bpost_label = new PsOrderBpostLabel((int)$row['id_order_bpost_label']);
				$is_retour = (int)$row['is_retour'];
				$has_retour = (int)$row['has_retour'];
				//$regular = (int)(!($is_intl && ($is_retour || $has_retour)));
				$odd = (bool)($is_intl && ($is_retour || $has_retour));
				if ($odd)
					$labels_odd[] = $order_bpost_label;
				else
					$labels_regular[$has_retour][] = $order_bpost_label;
			}

			if (count($labels_regular))
				$new_labels[] = $labels_regular;

			foreach ($labels_odd as $label)
				$new_labels[][$label->has_retour][] = $label;
		}

		return $new_labels;
	}

	private function _getAllEmptyLabels()
	{
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
		SELECT `id_order_bpost_label`, `is_retour`, `has_retour`
		FROM `'._DB_PREFIX_.'order_bpost_label`
		WHERE `id_order_bpost` = '.$this->id.' AND barcode IS NULL
		ORDER BY id_order_bpost_label ASC');
	}

}