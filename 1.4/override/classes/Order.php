<?php
/**
 * 2014 Stigmi
 *
 * @author    Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Order extends OrderCore
{
	public $reference;

	/**
	 * @param int|null $id
	 * @param int|null $id_lang
	 */
	public function __construct($id = null, $id_lang = null)
	{
		parent::__construct($id, $id_lang);

		$this->reference = $this->id;
	}

	public function getFields()
	{
		parent::validateFields();

		$fields['id_address_delivery'] = (int)($this->id_address_delivery);
		$fields['id_address_invoice'] = (int)($this->id_address_invoice);
		$fields['id_cart'] = (int)($this->id_cart);
		$fields['id_currency'] = (int)($this->id_currency);
		$fields['id_lang'] = (int)($this->id_lang);
		$fields['id_customer'] = (int)($this->id_customer);
		$fields['id_carrier'] = (int)($this->id_carrier);
		$fields['secure_key'] = pSQL($this->secure_key);
		$fields['payment'] = pSQL($this->payment);
		$fields['module'] = pSQL($this->module);
		$fields['conversion_rate'] = (float)($this->conversion_rate);
		$fields['recyclable'] = (int)($this->recyclable);
		$fields['gift'] = (int)($this->gift);
		$fields['gift_message'] = pSQL($this->gift_message);
		$fields['shipping_number'] = pSQL($this->shipping_number);
		$fields['total_discounts'] = (float)($this->total_discounts);
		$fields['total_paid'] = (float)($this->total_paid);
		$fields['total_paid_real'] = (float)($this->total_paid_real);
		$fields['total_products'] = (float)($this->total_products);
		$fields['total_products_wt'] = (float)($this->total_products_wt);
		$fields['total_shipping'] = (float)($this->total_shipping);
		$fields['carrier_tax_rate'] = (float)($this->carrier_tax_rate);
		$fields['total_wrapping'] = (float)($this->total_wrapping);
		$fields['invoice_number'] = (int)($this->invoice_number);
		$fields['delivery_number'] = (int)($this->delivery_number);
		$fields['invoice_date'] = pSQL($this->invoice_date);
		$fields['delivery_date'] = pSQL($this->delivery_date);
		$fields['valid'] = (int)($this->valid) ? 1 : 0;
		$fields['date_add'] = pSQL($this->date_add);
		$fields['date_upd'] = pSQL($this->date_upd);
		$fields['reference'] = pSQL($this->reference);

		return $fields;
	}
}