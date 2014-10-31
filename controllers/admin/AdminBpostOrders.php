<?php
/**
 * 2014 Stigmi
 *
 * @author    Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit();

require_once(_PS_MODULE_DIR_.'bpostshm/bpostshm.php');
require_once(_PS_MODULE_DIR_.'bpostshm/classes/Service.php');

class AdminBpostOrders extends ModuleAdminController
{
	public $statuses = array(
		'OPEN',
		'PENDING',
		'CANCELLED',
		'COMPLETED',
		'ON-HOLD',
		'PRINTED',
	);
	protected $identifier = 'reference';

	/* bpost orders are displayed into Orders > bpost depending on their PS order state */
	private $ps_order_states = array(2, 3, 4, 5, 9, 12);

	private $tracking_url = 'http://track.bpost.be/etr/light/performSearch.do';
	private $tracking_params = array(
		'searchByCustomerReference' => true,
		'oss_language' => '',
		'customerReference' => '',
	);

	public function __construct()
	{
		$this->table = 'order_label';
		$this->lang = false;
		$this->explicitSelect = true;
		$this->deleted = false;
		$this->list_no_link = true;
		$this->context = Context::getContext();

		$this->bootstrap = true;
		$this->show_filters = true;
		$this->module = new BpostShm();
		$this->service = Service::getInstance($this->context);

		$this->actions = array(
			'addLabel',
			'printLabels',
			'markTreated',
			'sendTTEmail',
			'createRetour',
			'view',
			'cancel',
		);

		$this->bulk_actions = array(
			'markTreated' => array('text' => $this->l('Mark treated'), 'confirm' => $this->l('Mark order as treated?')),
			'printLabels' => array('text' => $this->l('Print labels')),
			'sendTTEmail' => array('text' => $this->l('Send T&T e-mail'), 'confirm' => $this->l('Send Track & Trace e-mail to recipient?')),
		);

		$this->_select = '
		a.`reference` as print,
		a.`reference` as t_t,
		COUNT(a.`reference`) as count
		';

		$this->_join = '
		LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`reference` = SUBSTRING(a.`reference`, 8))
		LEFT JOIN `'._DB_PREFIX_.'order_carrier` oc ON (oc.`id_order` = o.`id_order`)
		LEFT JOIN `'._DB_PREFIX_.'carrier` c ON (c.`id_carrier` = oc.`id_carrier`)
		';

		$this->_where = '
		AND a.status IN("'.implode('", "', $this->statuses).'")
		AND o.current_state IN('.implode(', ', $this->ps_order_states).', '
			.(int)Configuration::get('BPOST_ORDER_STATE_TREATED_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id)).')
		AND DATEDIFF(NOW(), a.date_add) <= 14
		';

		$id_bpost_carriers = array_keys($this->service->delivery_methods_list);
		if ($references = Db::getInstance()->executeS('
			SELECT id_reference FROM `'._DB_PREFIX_.'carrier` WHERE id_carrier IN ('.implode(', ', $id_bpost_carriers).')'))
		{
			foreach ($references as $reference)
				$id_bpost_carriers[] = (int)$reference['id_reference'];
		}
		$this->_where .= '
		AND (
		oc.id_carrier IN ("'.implode('", "', $id_bpost_carriers).'")
		OR c.id_reference IN ("'.implode('", "', $id_bpost_carriers).'")
		)';

		$this->_group = 'GROUP BY(a.`reference`)';
		if (!Tools::getValue($this->table.'Orderby'))
			$this->_orderBy = 'o.id_order';
		if (!Tools::getValue($this->table.'Orderway'))
			$this->_orderWay = 'DESC';

		$this->fields_list = array(
		'print' => array(
			'title' => '',
			'align' => 'center',
			'callback' => 'getPrintIcon',
			'search' => false,
			'orderby' => false,
		),
		't_t' => array(
			'title' => '',
			'align' => 'center',
			'callback' => 'getTTIcon',
			'search' => false,
			'orderby' => false,
		),
		/*'id_order' => array(
			'title' => $this->l('Order n°'),
			'align' => 'center',
			'width' => 45,
			'filter_key' => 'o!id_order',
		),*/
		'reference' => array(
			'title' => $this->l('Reference'),
			'align' => 'left',
			'filter_key' => 'a!reference',
		),
		/*
		'delivery_method' => array(
			'title' => $this->l('Delivery method'),
			'search' => false,
			'filter_key' => 'a!delivery_method',
		),
		*/
		'delivery_method' => array(
			'title' => $this->l('Delivery method'),
			'search' => false,
			//'filter_key' => 'a!delivery_method',
			'callback' => 'getDeliveryMethod',
		),
		'recipient' => array(
			'title' => $this->l('Recipient'),
			'filter_key' => 'a!recipient',
		),
		/*
		'status' => array(
			'title' => $this->l('Status'),
		),
		*/
		'status' => array(
			'title' => $this->l('Status'),
			'callback' => 'getCurrentStatus',
		),
		'date_add' => array(
			'title' => $this->l('Creation date'),
			'align' => 'right',
			'type' => 'datetime',
			'filter_key' => 'a!date_add'
		),
		'count' => array(
			'title' => $this->l('Labels'),
			'align' => 'center',
			'search' => false,
			'orderby' => false,
		),
		'current_state' => array(
			'title' => $this->l('Order state'),
			'align' => 'center',
			'class' => 'order_state',
		)
		);

		$this->shopLinkType = 'shop';
		$this->shopShareDatas = Shop::SHARE_ORDER;

		parent::__construct();
	}

	public function initContent()
	{
		if (!$this->viewAccess())
		{
			$this->errors[] = Tools::displayError('You do not have permission to view this.');
			return;
		}

		$this->getLanguages();
		$this->initToolbar();
		$this->initTabModuleList();

		if ($this->display == 'view')
		{
			// Some controllers use the view action without an object
			if ($this->className)
				$this->loadObject(true);
			$this->content .= $this->renderView();
		}
		else
			parent::initContent();

		$this->addJqueryPlugin(array('idTabs'));
		$this->context->smarty->assign('content', $this->content);
	}

	public function initProcess()
	{
		parent::initProcess();

		if (empty($this->errors))
		{
			$reference 	= (string)Tools::getValue('reference');

			if (Tools::getIsset('addLabel'.$this->table))
				$this->jsonEncode($this->service->addLabel($reference));
			elseif (Tools::getIsset('printLabels'.$this->table))
			{
				$context_shop_id = (isset($this->context->shop) && !is_null($this->context->shop->id) ? $this->context->shop->id : 1);
				$links = $this->printLabels($reference);

				if (Configuration::get('BPOST_LABEL_TT_INTEGRATION_'.$context_shop_id) && !empty($links))
					$this->sendTTEmail($reference);

				$this->jsonEncode(array('links' => $links));
			}
			elseif (Tools::getIsset('markTreated'.$this->table))
				$this->jsonEncode($this->markOrderTreated($reference));
			elseif (Tools::getIsset('sendTTEmail'.$this->table))
			{
				$response = $this->sendTTEmail($reference);

				if (!empty($this->errors))
					$response = array('errors' => $this->errors);

				$this->jsonEncode($response);
			}
			elseif (Tools::getIsset('createRetour'.$this->table))
			{
				$response = array(
					'errors' => array(),
					'links' => array(),
				);

				$ps_order = Order::getByReference(Tools::substr($reference, 7))->getFirst();

				foreach (array_keys($this->module->shipping_methods) as $shipping_method)
					if ((int)$ps_order->id_carrier == (int)Configuration::get('BPOST_SHIP_METHOD_'.$shipping_method.'_ID_CARRIER_'
						.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id)))
					{
						$i = 1;

						$pdf_dir = _PS_MODULE_DIR_.'bpostshm/pdf';
						if (!is_dir($pdf_dir))
							mkdir($pdf_dir, 0755);
						$pdf_dir .= '/'.$reference;
						if (!is_dir($pdf_dir))
							mkdir($pdf_dir, 0755);
						$pdf_dir .= '/retours';
						if (!is_dir($pdf_dir))
							mkdir($pdf_dir, 0755);

						$files = scandir($pdf_dir);
						if (!empty($files) && is_array($files))
							foreach ($files as $file)
								if (!in_array($file, array('.', '..')) && !is_dir($pdf_dir.'/'.$file))
								{
									$response['links'][] = _MODULE_DIR_.'bpostshm/pdf/'.$reference.'/retours/'.$i.'.pdf';
									$i++;
								}

						if ($this->service->makeOrder($ps_order->id, $shipping_method, true))
						{
							$context_shop_id = (isset($this->context->shop) && !is_null($this->context->shop->id) ? $this->context->shop->id : 1);

							if ($links = $this->service->createLabelForOrder(
								$reference,
								Configuration::get('BPOST_LABEL_PDF_FORMAT_'.$context_shop_id),
								(bool)Configuration::get('BPOST_LABEL_RETOUR_LABEL_'.$context_shop_id)))
							{
								foreach ($links as $label)
								{
									$this->service->updatePSLabelBarcode($reference, $label->getBarcode());

									$file = $pdf_dir.'/'.$i.'.pdf';
									$fp = fopen($file, 'w');
									fwrite($fp, $label->getBytes());
									fclose($fp);

									$response['links'][] = _MODULE_DIR_.'bpostshm/pdf/'.$reference.'/retours/'.$i.'.pdf';
									$i++;
								}

								$this->service->updatePSLabelStatus($reference, 'PRINTED');
							}
						}

						break;
					}

				$this->jsonEncode($response);
			}
			elseif (Tools::getIsset('cancel'.$this->table))
			{
				$errors = array();
				$response = true;

				$id_order_state = null;
				$order_states = OrderState::getOrderStates($this->context->language->id);
				foreach ($order_states as $order_state)
					if ('order_canceled' == $order_state['template'])
					{
						$id_order_state = $order_state['id_order_state'];
						break;
					}

				if (is_null($id_order_state))
					$errors[] = Tools::displayError('Please create a "Cancel" order state using "order_canceled" template.');

				$pdf_dir = _PS_MODULE_DIR_.'bpostshm/pdf/'.$reference;
				if (is_dir($pdf_dir) && opendir($pdf_dir))
					$errors[] = Tools::displayError('The order has one or more barcodes linked in the bpost Shipping Manager.');

				if (!empty($errors))
					$this->jsonEncode(array('errors' => $errors));

				$ps_order = Order::getByReference(Tools::substr($reference, 7))->getFirst();
				$ps_order->current_state = (int)$id_order_state;
				$response = $response && $ps_order->save();

				// Create new OrderHistory
				$history = new OrderHistory();
				$history->id_order = $ps_order->id;
				$history->id_employee = (int)$this->context->employee->id;

				$use_existings_payment = false;
				if (!$ps_order->hasInvoice())
					$use_existings_payment = true;
				$history->changeIdOrderState((int)$id_order_state, $ps_order, $use_existings_payment);

				$carrier = new Carrier($ps_order->id_carrier, $ps_order->id_lang);
				$template_vars = array();
				if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $ps_order->shipping_number)
					$template_vars = array('{followup}' => str_replace('@', $ps_order->shipping_number, $carrier->url));
				// Save all changes
				$response = $response && $history->addWithemail(true, $template_vars);

				$response = $response && $this->service->updateOrderStatus($reference, 'CANCELLED');
				$this->jsonEncode($response);

			}
		}
	}

	/**
	 * @param mixed $content
	 */
	private function jsonEncode($content)
	{
		header('Content-Type: application/json');
		die(Tools::jsonEncode($content));
	}

	/**
	 * @param string $reference
	 * @return array
	 */
	private function printLabels($reference = '')
	{
		$links = array();

		if (empty($reference))
			return $links;

		$context_shop_id = (isset($this->context->shop) && !is_null($this->context->shop->id) ? $this->context->shop->id : 1);
		$do_not_open = array('.', '..', 'labels');
		$i = 1;

		$pdf_dir = _PS_MODULE_DIR_.'bpostshm/pdf';
		if (!is_dir($pdf_dir))
			mkdir($pdf_dir, 0755);

		$pdf_dir .= '/'.$reference;
		if (!is_dir($pdf_dir))
			mkdir($pdf_dir, 0755);

		$files = scandir($pdf_dir);
		if (!empty($files) && is_array($files))
			foreach ($files as $file)
				if (!in_array($file, $do_not_open) && !is_dir($pdf_dir.'/'.$file))
				{
					$links[] = _PS_BASE_URL_._MODULE_DIR_.'bpostshm/pdf/'.$reference.'/'.$i.'.pdf';
					$i++;
				}

		if ($labels = $this->service->createLabelForOrder(
			$reference,
			Configuration::get('BPOST_LABEL_PDF_FORMAT_'.$context_shop_id),
			(bool)Configuration::get('BPOST_LABEL_RETOUR_LABEL_'.$context_shop_id)))
		{
			foreach ($labels as $label)
			{
				$this->service->updatePSLabelBarcode($reference, $label->getBarcode());

				$file = $pdf_dir.'/'.$i.'.pdf';
				$fp = fopen($file, 'w');
				fwrite($fp, $label->getBytes());
				fclose($fp);

				$links[] = _MODULE_DIR_.'bpostshm/pdf/'.$reference.'/'.$i.'.pdf';
				$i++;
			}

			$this->service->updatePSLabelStatus($reference, 'PRINTED');
		}

		$pdf_dir .= '/retours';
		if (is_dir($pdf_dir))
		{
			$i = 1;
			$files = scandir($pdf_dir);
			if (!empty($files) && is_array($files))
				foreach ($files as $file)
					if (!in_array($file, $do_not_open) && !is_dir($pdf_dir.'/'.$file))
					{
						$links[] = _PS_BASE_URL_._MODULE_DIR_.'bpostshm/pdf/'.$reference.'/retours/'.$i.'.pdf';
						$i++;
					}
		}

		return $links;
	}

	/**
	 * @param string $reference
	 * @return bool
	 */
	private function sendTTEmail($reference = '')
	{
		if (empty($reference))
			return false;

		$pdf_dir = _PS_MODULE_DIR_.'bpostshm/pdf/'.$reference;
		// disable if labels are not PRINTED
		if (!is_dir($pdf_dir) || !opendir($pdf_dir))
		{
			$this->errors[] = str_replace(
				'%reference%',
				$reference,
				$this->l('Order ref. %reference% was not treated : action is only available for orders that are printed.')
			);
			return false;
		}

		$response = true;
		$ps_order = Order::getByReference(Tools::substr($reference, 7))->getFirst();
		$tracking_url = $this->tracking_url;

		foreach ($this->tracking_params as $param => $value)
			if (empty($value) && false !== $value)
				switch ($param)
				{
					case 'searchByCustomerReference':
						$this->tracking_params[$param] = true;
						break;
					case 'oss_language':
						if (in_array($this->context->language->iso_code, array('de', 'fr', 'nl', 'en')))
							$this->tracking_params[$param] = $this->context->language->iso_code;
						else
							$this->tracking_params[$param] = 'en';
						break;
					case 'customerReference':
						$this->tracking_params[$param] = $reference;
						break;
					default:
						break;
				}

		$tracking_url .= '?'.http_build_query($this->tracking_params);
		$message = $this->l('Your order').' '.$ps_order->reference.' '.$this->l('can now be tracked here :')
			.' <a href="'.$tracking_url.'">'.$tracking_url.'</a>';

		$customer = new Customer($ps_order->id_customer);
		if (!Validate::isLoadedObject($customer))
			$this->errors[] = Tools::displayError('The customer is invalid.');
		else
		{
			//check if a thread already exist
			$id_customer_thread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($customer->email, $ps_order->id);
			if (!$id_customer_thread)
			{
				$customer_thread = new CustomerThread();
				$customer_thread->id_contact = 0;
				$customer_thread->id_customer = (int)$ps_order->id_customer;
				$customer_thread->id_shop = (int)$this->context->shop->id;
				$customer_thread->id_order = (int)$ps_order->id;
				$customer_thread->id_lang = (int)$this->context->language->id;
				$customer_thread->email = $customer->email;
				$customer_thread->status = 'open';
				$customer_thread->token = Tools::passwdGen(12);
				$customer_thread->add();
			}
			else
				$customer_thread = new CustomerThread((int)$id_customer_thread);

			$customer_message = new CustomerMessage();
			$customer_message->id_customer_thread = $customer_thread->id;
			$customer_message->id_employee = (int)$this->context->employee->id;
			$customer_message->message = $message;
			$customer_message->private = false;

			if (!$customer_message->add())
				$this->errors[] = Tools::displayError('An error occurred while saving the message.');
			else
			{
				$message = $customer_message->message;
				if (Configuration::get('PS_MAIL_TYPE', null, null, $ps_order->id_shop) != Mail::TYPE_TEXT)
					$message = Tools::nl2br($customer_message->message);

				$vars_tpl = array(
					'{lastname}' => $customer->lastname,
					'{firstname}' => $customer->firstname,
					'{id_order}' => $ps_order->id,
					'{order_name}' => $ps_order->getUniqReference(),
					'{message}' => $message,
				);

				Mail::Send((int)$ps_order->id_lang, 'order_merchant_comment',
					Mail::l('New message regarding your order', (int)$ps_order->id_lang), $vars_tpl, $customer->email,
					$customer->firstname.' '.$customer->lastname, null, null, null, null, _PS_MAIL_DIR_, true, (int)$ps_order->id_shop
				);
			}
		}

		if (!empty($this->errors))
			$response = false;

		return $response;
	}

	/**
	 * @param string $reference
	 * @return bool
	 */
	private function markOrderTreated($reference = '')
	{
		if (empty($reference))
			return false;

		$pdf_dir = _PS_MODULE_DIR_.'bpostshm/pdf/'.$reference;
		// disable if labels are not PRINTED
		if (!is_dir($pdf_dir) || !opendir($pdf_dir))
		{
			$this->errors[] = str_replace(
				'%reference%',
				$reference,
				$this->l('Order ref. %reference% was not treated : action is only available for orders that are printed.')
			);
			return false;
		}

		$response = true;
		$treated_status = Configuration::get('BPOST_ORDER_STATE_TREATED_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id));
		$ps_order = Order::getByReference(Tools::substr($reference, 7))->getFirst();
		$ps_order->current_state = (int)$treated_status;
		$response = $response && $ps_order->save();

		// Create new OrderHistory
		$history = new OrderHistory();
		$history->id_order = $ps_order->id;
		$history->id_employee = (int)$this->context->employee->id;

		$use_existings_payment = false;
		if (!$ps_order->hasInvoice())
			$use_existings_payment = true;
		$history->changeIdOrderState((int)$treated_status, $ps_order, $use_existings_payment);

		$carrier = new Carrier($ps_order->id_carrier, $ps_order->id_lang);
		$template_vars = array();
		if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $ps_order->shipping_number)
			$template_vars = array('{followup}' => str_replace('@', $ps_order->shipping_number, $carrier->url));
		// Save all changes
		$response = $response && $history->addWithemail(true, $template_vars);

		return $response;
	}

	/**
	 * Function used to render the list to display for this controller
	 */
	public function renderList()
	{
		if (!($this->fields_list && is_array($this->fields_list)))
			return false;
		$this->getList($this->context->language->id);

		$helper = new HelperList();
		$helper->module = new BpostShm();

		// Empty list is ok
		if (!is_array($this->_list))
		{
			$this->displayWarning($this->l('Bad SQL query', 'Helper').'<br />'.htmlspecialchars($this->_list_error));
			return false;
		}

		$this->tpl_list_vars = array_merge(
			$this->tpl_list_vars,
			array(
				'treated_status' =>
					Configuration::get('BPOST_ORDER_STATE_TREATED_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id)),
				'url_get_label' =>
					'index.php?tab=AdminOrders&addorder&token='.Tools::getAdminTokenLite('AdminOrders'),
			)
		);

		$this->setHelperDisplay($helper);
		$helper->tpl_vars = $this->tpl_list_vars;
		$helper->tpl_delete_link_vars = $this->tpl_delete_link_vars;

		// For compatibility reasons, we have to check standard actions in class attributes
		foreach ($this->actions_available as $action)
			if (!in_array($action, $this->actions) && isset($this->$action) && $this->$action)
				$this->actions[] = $action;
		$helper->is_cms = $this->is_cms;
		$list = $helper->generateList($this->_list, $this->fields_list);

		return $list;
	}

	public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
	{
		parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);

		if (!Tools::getValue($this->list_id.'_pagination'))
			$this->context->cookie->{$this->list_id.'_pagination'} = 50;
	}

	public function processbulkmarktreated()
	{
		$response = true;

		if (empty($this->boxes) || !is_array($this->boxes))
			$response = false;
		else
			foreach ($this->boxes as $reference)
				$response &= $response && $this->markOrderTreated($reference);

		return $response;
	}

	public function processbulkprintlabels()
	{
		$labels = array();

		if (empty($this->boxes) || !is_array($this->boxes))
			return false;
		else
			foreach ($this->boxes as $reference)
				$labels[] = $this->printLabels($reference);

		if (!empty($labels))
			$this->context->smarty->assign('labels', $labels);

		return true;
	}

	public function processbulksendttemail()
	{
		$response = true;

		if (empty($this->boxes) || !is_array($this->boxes))
			$response = false;
		else
			foreach ($this->boxes as $reference)
				$response &= $response && $this->sendTTEmail($reference);

		return $response;
	}

	/**
	 * @param string $delivery_method as stored
	 * @return string
	 */
	public function getDeliveryMethod($delivery_method = '')
	{
		if (empty($delivery_method))
			return;

		// format: slug[:option list]*
		// @bpost or @home:300|330
		$dm_options = explode(':', $delivery_method);
		$delivery_method = $dm_options[0];
		if (isset($dm_options[1]))
		{
			$dm_options = $this->service->getDeliveryOptions($dm_options[1]);
			$opts = '<ul style="list-style:none;font-size:11px;line-height:14px;padding:0;">';
			foreach ($dm_options as $key => $option)
				$opts .= '<li>+ '.$option.'</li>';

			$delivery_method .= $opts.'</ul>';
		}

		return $delivery_method;
	}

	/**
	 * @param string $delivery_method as stored
	 * @return string
	 */
	public function getCurrentStatus($status = '', $fields_list)
	{
		if (empty($status) || empty($fields_list))
			return;

		$current_status = $status;
		// $reference = $fields_list['reference'];
		// $current_state = (int)$fields_list['current_state'];
		// $current_status = (13 === $current_state) ? $this->service->getOrderStatus($reference) : $status;

		// $current_status = $this->service->getOrderStatus($reference);

		return $current_status;
	}

	/**
	 * @param string $reference
	 * @return string
	 */
	public function getPrintIcon($reference = '')
	{
		if (empty($reference))
			return;

		return '<img class="print" src="'._MODULE_DIR_.'bpostshm/views/img/icons/print.png"
			 data-labels="'.Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&printLabels'.$this->table.'&token='.$this->token).'"/>';
	}

	/**
	 * @param string $reference
	 * @return string
	 */
	public function getTTIcon($reference = '')
	{
		if (empty($reference))
			return;

		/*$ps_order = Order::getByReference(Tools::substr($reference, 7))->getFirst();
		$treated_status = Configuration::get('BPOST_ORDER_STATE_TREATED_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id));
		// do not display if order is not TREATED
		if ($ps_order->current_state != $treated_status)
			return;*/

		$pdf_dir = _PS_MODULE_DIR_.'bpostshm/pdf/'.$reference;
		// do not display if labels are not PRINTED
		if (!is_dir($pdf_dir) || !opendir($pdf_dir))
			return;

		$tracking_url = $this->tracking_url;
		foreach ($this->tracking_params as $param => $value)
			if (empty($value) && false !== $value)
				switch ($param)
				{
					case 'searchByCustomerReference':
						$this->tracking_params[$param] = true;
						break;
					case 'oss_language':
						if (in_array($this->context->language->iso_code, array('de', 'fr', 'nl', 'en')))
							$this->tracking_params[$param] = $this->context->language->iso_code;
						else
							$this->tracking_params[$param] = 'en';
						break;
					case 'customerReference':
						$this->tracking_params[$param] = $reference;
						break;
					default:
						break;
				}
		$tracking_url .= '?'.http_build_query($this->tracking_params);

		return '<a href="'.$tracking_url.'" target="_blank" title="'.$this->l('View Track & Trace status').'">
			<img class="t_t" src="'._MODULE_DIR_.'bpostshm/views/img/icons/track_and_trace.png" /></a>';
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displayAddLabelLink($token = null, $reference = '')
	{
		if (empty($reference))
			return;

		$tpl_vars = array(
			'action' => $this->l('Add label'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&addLabel'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);

		/*$context_shop_id = (isset($this->context->shop) && !is_null($this->context->shop->id) ? $this->context->shop->id : 1);

		// Disable if retours auto-generation is OFF
		if (!(bool)Configuration::get('BPOST_LABEL_RETOUR_LABEL_'.$context_shop_id))
		{
			$pdf_dir = _PS_MODULE_DIR_.'bpostshm/pdf/'.$reference.'/retours';
			// disable if labels are not PRINTED
			if (is_dir($pdf_dir))
				$tpl_vars['disabled'] = $this->l('A retour has already been created.');
		}*/

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displayPrintLabelsLink($token = null, $reference = '')
	{
		if (empty($reference))
			return;

		$tpl_vars = array(
			'action' => $this->l('Print labels'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&printLabels'.$this->table
				.'&token='.($token != null ? $token : $this->token))
		);

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displayMarkTreatedLink($token = null, $reference = '')
	{
		if (empty($reference))
			return;

		$tpl_vars = array(
			'action' => $this->l('Mark treated'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&markTreated'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);

		$pdf_dir = _PS_MODULE_DIR_.'bpostshm/pdf/'.$reference;
		// disable if labels are not PRINTED
		if (!is_dir($pdf_dir) || !opendir($pdf_dir))
			$tpl_vars['disabled'] = $this->l('Actions are only available for orders that are printed.');

		$ps_order = Order::getByReference(Tools::substr($reference, 7))->getFirst();
		$treated_status = Configuration::get('BPOST_ORDER_STATE_TREATED_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id));
		// disable if order already is treated
		if ($ps_order->current_state == $treated_status)
			$tpl_vars['disabled'] = $this->l('Order is already treated.');

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displaySendTTEmailLink($token = null, $reference = '')
	{
		if (empty($reference))
			return;

		$context_shop_id = (isset($this->context->shop) && !is_null($this->context->shop->id) ? $this->context->shop->id : 1);

		// Do not display if T&T mails are automatically sent
		if ((bool)Configuration::get('BPOST_LABEL_TT_INTEGRATION_'.$context_shop_id))
			return;

		$tpl_vars = array(
			'action' => $this->l('Send Track & Trace e-mail'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&sendTTEmail'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);

		$pdf_dir = _PS_MODULE_DIR_.'bpostshm/pdf/'.$reference;
		if (!is_dir($pdf_dir) || !opendir($pdf_dir))
		// disable if labels are not PRINTED
			$tpl_vars['disabled'] = $this->l('Actions are only available for orders that are printed.');

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displayCreateRetourLink($token = null, $reference = '')
	{
		if (empty($reference))
			return;

		$context_shop_id = (isset($this->context->shop) && !is_null($this->context->shop->id) ? $this->context->shop->id : 1);

		// Do not display if retours are automatically generated
		if ((bool)Configuration::get('BPOST_LABEL_RETOUR_LABEL_'.$context_shop_id))
			return;

		$tpl_vars = array(
			'action' => $this->l('Create retour'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&createRetour'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);

		/*$pdf_dir = _PS_MODULE_DIR_.'bpostshm/pdf/'.$reference;
		// disable if labels are not PRINTED
		if (!is_dir($pdf_dir) || !opendir($pdf_dir))
			$tpl_vars['disabled'] = $this->l('Actions are only available for orders that are printed.');
*/
		$ps_order = Order::getByReference(Tools::substr($reference, 7))->getFirst();
		$address_delivery = new Address($ps_order->id_address_delivery);
		$id_country = (int)$address_delivery->id_country;
		foreach (array('België', 'Belgique', 'Belgium') as $country)
			if ($id_belgium = Country::getIdByName(null, $country))
				break;
		// disable if no Belgium found or if order has been placed elsewhere
		if (empty($id_belgium) || $id_belgium !== $id_country)
			$tpl_vars['disabled'] = $this->l('The creation of international retour orders are currently not supported.
				Please contact your bpost account manager for more information.');

		/*$pdf_dir = _PS_MODULE_DIR_.'bpostshm/pdf/'.$reference.'/retours';
		// disable if retour labels have already been PRINTED
		if (is_dir($pdf_dir))
			$tpl_vars['disabled'] = $this->l('A retour has already been created.');
*/
		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displayViewLink($token = null, $reference = '')
	{
		if (empty($reference))
			return;

		$tpl_vars = array(
			'action' => $this->l('Open order'),
			'target' => '_blank',
		);

		$ps_order = Order::getByReference(Tools::substr($reference, 7))->getFirst();
		$tpl_vars['href'] = 'index.php?tab=AdminOrders&vieworder&id_order='.(int)$ps_order->id.'&token='.Tools::getAdminTokenLite('AdminOrders');

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displayCancelLink($token = null, $reference = '')
	{
		if (empty($reference))
			return;

		$tpl_vars = array(
			'action' => $this->l('Cancel order'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&cancel'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);

		$pdf_dir = _PS_MODULE_DIR_.'bpostshm/pdf/'.$reference;
		// disable if labels have already been PRINTED
		if (is_dir($pdf_dir))
			$tpl_vars['disabled'] = $this->l('Only open orders can be cancelled.');

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}
}
