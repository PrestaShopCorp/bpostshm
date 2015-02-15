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

class AdminOrdersBpost extends ModuleAdminController
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
	private $ps_order_state_rejects = array(1, 6, 7, 8, 10, 11);

	private $tracking_url = 'http://track.bpost.be/etr/light/performSearch.do';
	private $tracking_params = array(
		'searchByCustomerReference' => true,
		'oss_language' => '',
		'customerReference' => '',
	);

	public function __construct()
	{
		$this->table = 'order_bpost';
		$this->lang = false;
		$this->explicitSelect = true;
		$this->deleted = false;
		$this->list_no_link = true;
		$this->context = Context::getContext();

		$iso_code = $this->context->language->iso_code;
		$iso_code = in_array($iso_code, array('de', 'fr', 'nl', 'en')) ? $iso_code : 'en';
		$this->tracking_params['oss_language'] = $iso_code;
		$this->affectAdminTranslation($iso_code);

		// cached current_row while building list
		// always false after display for any action
		$this->current_row = false;
		$this->bpost_treated_state = (int)Configuration::get('BPOST_ORDER_STATE_TREATED');

		$this->bootstrap = true;
		$this->show_filters = true;
		$this->module = new BpostShm();
		// service needs to be shop context dependant.
		// $this->service = Service::getInstance($this->context);

		$this->actions = array(
			'addLabel',
			'createRetour',
			'printLabels',
			'markTreated',
			'sendTTEmail',
			// 'createRetour',
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
		a.`shm`,
		COUNT(obl.`id_order_bpost_label`) as count,
		SUM(obl.`barcode` IS NOT NULL) AS count_printed,
		SUM(obl.`is_retour` = 1) AS count_retours,
		SUM(obl.`has_retour` = 1) AS count_auto_retours 
		';

		$this->_join = '
		LEFT JOIN `'._DB_PREFIX_.'order_bpost_label` obl ON (a.`id_order_bpost` = obl.`id_order_bpost`)
		LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = SUBSTRING(a.`reference`, 8))
		LEFT JOIN `'._DB_PREFIX_.'order_carrier` oc ON (oc.`id_order` = o.`id_order`)
		LEFT JOIN `'._DB_PREFIX_.'carrier` c ON (c.`id_carrier` = oc.`id_carrier`)
		';

		$this->_where = '
		AND obl.status IN("'.implode('", "', $this->statuses).'")
		AND a.current_state NOT IN('.implode(', ', $this->ps_order_state_rejects).')
		AND DATEDIFF(NOW(), a.date_add) <= 14
		';

		$id_bpost_carriers = array_values($this->module->getIdCarriers());
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
		'reference' => array(
			'title' => $this->l('Reference'),
			'align' => 'left',
			'filter_key' => 'a!reference',
		),
		'delivery_method' => array(
			'title' => $this->l('Delivery method'),
			'search' => false,
			'callback' => 'getDeliveryMethod',
		),
		'recipient' => array(
			'title' => $this->l('Recipient'),
			'filter_key' => 'a!recipient',
		),
		'status' => array(
			'title' => $this->l('Status'),
			'filter_key' => 'obl!status',
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
			'callback' => 'getLabelsCount',
			'search' => false,
			'orderby' => false,
		),
		'current_state' => array(
			'title' => $this->l('Order state'),
			'filter_key' => 'a!current_state',
			'align' => 'center',
			'class' => 'order_state',
		)
		);

		$this->shopLinkType = 'shop';
		$this->shopShareDatas = Shop::SHARE_ORDER;

		// Just for Autoload
		Service::getInstance($this->context);
		parent::__construct();
	}

	public function initContent()
	{
		if (!$this->viewAccess())
		{
			$this->_errors[] = Tools::displayError('You do not have permission to view this.');
			return;
		}

		$this->getLanguages();
		$this->initToolbar();
		if (method_exists($this, 'initTabModuleList'))  // method not in earlier PS 1.5 < .6.2
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

		$reference = (string)Tools::getValue('reference');
		if (empty($this->errors) && !empty($reference))
		{
			$response = array();
			// service needs to be shop context dependant
			$this->setRowContext($reference);
			$service = new Service($this->context);

			if (Tools::getIsset('addLabel'.$this->table))
			{
				if (!$response = $service->addLabel($reference))
					$response['errors'] = 'Unable to add Label to order ['.$reference.'] Please check logs for errors.';

				$this->jsonEncode($response);
			}
			elseif (Tools::getIsset('createRetour'.$this->table))
			{
				if (!$response = $service->addLabel($reference, true))
					$response['errors'] = 'Unable to add Retour Label to order ['.$reference.'] Please check logs for errors.';

				$this->jsonEncode($response);
			}
			elseif (Tools::getIsset('printLabels'.$this->table))
			{
				$links = $service->printLabels($reference);
				if (isset($links['Error']))
					$response['errors'] = $links['Error'];
				else
					$response['links'] = $links;

				if (Configuration::get('BPOST_LABEL_TT_INTEGRATION') && !empty($links))
					$this->sendTTEmail($reference);

				$this->jsonEncode($response);
			}
			elseif (Tools::getIsset('markTreated'.$this->table))
			{
				if (!$response = $this->changeOrderState($reference, $this->getOrderState('Treated')))
					$response['errors'] = $this->_errors;

				$this->jsonEncode($response);
			}
			elseif (Tools::getIsset('sendTTEmail'.$this->table))
			{
				if (!$response = $this->sendTTEmail($reference))
					$response['errors'] = $this->_errors;

				$this->jsonEncode($response);
			}
			elseif (Tools::getIsset('cancel'.$this->table))
			{
				if (!$response = $this->changeOrderState($reference, $this->getOrderState('Cancelled')))
					$response['errors'] = $this->_errors;

				$this->jsonEncode($response);
			}
		}
	}

	/**
	 * override PS controllers broken translation
	 * @author Serge <serge@stigmi.eu>
	 * @param  string  $string       string to translate
	 * @return string                translated string if found or $string
	 */
	protected function l($string, $class = 'AdminTab', $addslashes = false, $htmlentities = true)
	{
		$class = get_class($this); // always
		//  $addslashes = false
		$htmlentities = false; // always
		return Translate::getAdminTranslation($string, $class, $addslashes, $htmlentities);
	}

	/**
	 * insert this controllers translation strings into
	 * globally retrieved AdminTab translations
	 * @author Serge <serge@stigmi.eu>
	 * @param  string $iso_code
	 * @return None
	 */
	private function affectAdminTranslation($iso_code = 'en')
	{
		global $_LANGADM;

		$class_name = get_class($this);
		$module = isset($this->module) ? $this->module : 'bpostshm';
		$needle = Tools::strtolower($class_name).'_';
		$lang_file = _PS_MODULE_DIR_.$module.'/'.$iso_code.'.php';
		if (file_exists($lang_file))
		{
			$_MODULE = array();
			require $lang_file;
			foreach ($_MODULE as $key => $value)
				if (strpos($key, $needle))
					$_LANGADM[str_replace($needle, $class_name, strip_tags($key))] = $value;

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
	 * construct an Order state array 
	 * id => id_order_state (default 0 is error)
	 * set_b4_printed => bool (if true only valid 'before' PRINTED)
	 * for use in changeOrderState which makes the change.
	 * @author Serge <serge@stigmi.eu>
	 * @param  string $state_name Order state Name
	 * @return bool             if false $this->_errors has the message
	 */
	private function getOrderState($state_name = '')
	{
		$order_state = array(
			'id' => 0,
			'set_b4_printed' => false,
			);

		$error = $this->l('Order ref. %reference% was not %state% : action is only available for orders %when% they are printed.');
		switch ((string)$state_name)
		{
			case 'Cancelled':
				$ps_order_states = OrderState::getOrderStates($this->context->language->id);
				foreach ($ps_order_states as $ps_order_state)
					if ('order_canceled' == $ps_order_state['template'])
					{
						$order_state['id'] = $ps_order_state['id_order_state'];
						break;
					}

				if ($order_state['id'])
					$order_state['set_b4_printed'] = true;
				else
					$error = $this->l('Please create a "Cancelled" order state using "order_canceled" template.');

				break;

			case 'Treated':
				$order_state['id'] = $this->bpost_treated_state;
				break;

			default:
				$error = $this->l('Invalid order state');

		}

		$when = $order_state['set_b4_printed'] ? $this->l('before') : $this->l('after');
		$order_state['error'] = str_replace(
			array(
				'%state%',
				'%when%'
				),
			array(
				Tools::strtolower($this->l($state_name)),
				$when
				),
			$error);

		return $order_state;
	}

	/**
	 * @param string $reference
	 * @return bool
	 */
	private function changeOrderState($reference = '', $order_state = '')
	{
		if (empty($reference) || empty($order_state))
			return false;

		$response = true;
		$order_bpost = PsOrderBpost::getByReference($reference);

		// are we allowed ? if labels are PRINTED
		//if (empty($order_state['id']) || !($order_state['set_b4_printed'] xor (bool)$order_bpost->countPrinted()))
		if (empty($order_state['id']) || !($order_state['set_b4_printed'] ^ (bool)$order_bpost->countPrinted()))
		{

			$this->_errors[] = str_replace(
				'%reference%',
				$reference,
				$order_state['error']
			);
			return false;
		}

		$order_bpost->current_state = (int)$order_state['id'];
		$order_bpost->save();

		$ps_order = new Order((int)Service::getOrderIDFromReference($reference));

		// Create new OrderHistory
		$history = new OrderHistory();
		$history->id_order = $ps_order->id;
		$history->id_employee = (int)$this->context->employee->id;
		// 1.5+ specific $use_existing_payment
		$use_existings_payment = !$ps_order->hasInvoice();
		$history->changeIdOrderState((int)$order_state['id'], $ps_order->id, $use_existings_payment);

		$carrier = new Carrier($ps_order->id_carrier, $ps_order->id_lang);
		$template_vars = array();
		if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $ps_order->shipping_number)
			$template_vars = array('{followup}' => str_replace('@', $ps_order->shipping_number, $carrier->url));
		// Save all changes
		$response = $response && $history->addWithemail(true, $template_vars);

		return $response;
	}

	/**
	 * @param string $reference
	 * @return bool
	 */
	private function sendTTEmail($reference = '')
	{
		if (empty($reference))
			return false;

		$response = true;
		$order_bpost = PsOrderBpost::getByReference($reference);
		$order_state = $this->getOrderState('Treated');

		// are we allowed ? if labels are PRINTED
		if (!$order_bpost->countPrinted())
		{

			$this->_errors[] = str_replace(
				'%reference%',
				$reference,
				$order_state['error']
			);
			return false;
		}

		$tracking_url = $this->tracking_url;
		$params = $this->tracking_params;
		$params['customerReference'] = $reference;
		$tracking_url .= '?'.http_build_query($params);

		$ps_order = new Order((int)Service::getOrderIDFromReference($reference));
		// $ps_cart = new Cart((int)$ps_order->id_cart);
		$message = $this->l('Your order').' '.$ps_order->reference.' '.$this->l('can now be tracked here :')
			.' <a href="'.$tracking_url.'">'.$tracking_url.'</a>';

		$customer = new Customer($ps_order->id_customer);
		if (!Validate::isLoadedObject($customer))
			$this->_errors[] = Tools::displayError('The customer is invalid.');
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

			try {
				if (!$customer_message->add())
					$this->_errors[] = 'Ref '.$reference.': '.Tools::displayError('An error occurred while saving the message.');
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

			} catch (Exception $e) {
				$this->_errors[] = $e->getMessage();

			}
		}

		if (!empty($this->_errors))
			$response = false;

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
		elseif (empty($this->_list))
			$this->bulk_actions = array();

		$this->tpl_list_vars = array_merge(
			$this->tpl_list_vars,
			array(
				'treated_status' =>
					$this->bpost_treated_state,
				'str_tabs' =>
					array(
						'open' => $this->l('Open'),
						'treated' => $this->l('Treated'),
						),
				'reload_href' =>
					self::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminOrdersBpost'),
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
		if (empty($this->boxes) || !is_array($this->boxes))
			return false;

		$response = true;
		foreach ($this->boxes as $reference)
			$response &= $response && $this->changeOrderState($reference, $this->getOrderState('Treated'));

		if (!$response)
			$this->context->smarty->assign('errors', $this->_errors);

		return $response;
	}

	public function processbulkprintlabels()
	{
		if (empty($this->boxes) || !is_array($this->boxes))
			return false;

		$labels = array();
		foreach ($this->boxes as $reference)
		{
			$this->setRowContext($reference);
			$service = new Service($this->context);

			$links = $service->printLabels($reference);
			if (isset($links['Error']))
					$this->_errors[] = $links['Error'];
				else
					$labels[] = $links;
		}

		if (!empty($this->_errors))
			$this->context->smarty->assign('errors', $this->_errors);

		if (!empty($labels))
			$this->context->smarty->assign('labels', $labels);

		return true;
	}

	public function processbulksendttemail()
	{
		if (empty($this->boxes) || !is_array($this->boxes))
			return false;

		$response = true;
		foreach ($this->boxes as $reference)
			$response &= $response && $this->sendTTEmail($reference);

		if (!$response)
			$this->context->smarty->assign('errors', $this->_errors);

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
			$service = Service::getInstance($this->context);
			$dm_options = $service->getDeliveryOptions($dm_options[1]);
			$opts = '<ul style="list-style:none;font-size:11px;line-height:14px;padding:0;">';
			// foreach ($dm_options as $key => $option)
			foreach ($dm_options as $option)
				$opts .= '<li>+ '.$option.'</li>';

			$delivery_method .= $opts.'</ul>';
		}

		return $delivery_method;
	}

	/**
	 * @param string $status as stored
	 * @return string
	 */
	public function getCurrentStatus($status = '')
	{
		$fields_list = $this->current_row;
		if (empty($status) || empty($fields_list))
			return;

		$count_printed = (int)$fields_list['count_printed'];
		$current_status = $status;
		$current_status .= $count_printed ? ' ('.$count_printed.')' : '';

		return $current_status;
	}

	/**
	 * @param string $status as stored
	 * @return string
	 */
	public function getLabelsCount($count = '')
	{
		$fields_list = $this->current_row;
		if (empty($count) || empty($fields_list))
			return;

		$count_retours = (int)$fields_list['count_retours'];
		$count_normal = $count - $count_retours;

		$reduced_size = $count_normal ? 'font-size:10px;' : '';
		$plus = $count_normal ? ' +' : '';
		$disp_retours = '<span style="'.$reduced_size.'color:silver;">'.$plus.$count_retours.'R</span>';

		$current_count = $count_normal ?
			$count_normal.($count_retours ? $disp_retours : '') :
			$disp_retours;

		return $current_count;
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
		$fields_list = $this->current_row;
		if (empty($reference) || empty($fields_list) || empty($fields_list['count_printed'])) //(!$fields_list['count_printed']))
			return;

		$tracking_url = $this->tracking_url;
		$params = $this->tracking_params;
		$params['customerReference'] = $reference;

		$tracking_url .= '?'.http_build_query($params);

		return '<a href="'.$tracking_url.'" target="_blank" title="'.$this->l('View Track & Trace status').'">
			<img class="t_t" src="'._MODULE_DIR_.'bpostshm/views/img/icons/track_and_trace.png" /></a>';
	}

	/**
	 * [setCurrentRow]
	 * @param  string $reference 
	 * currentRow cached in member var current_row
	 * usefull while building the list since not all
	 * callbacks are called wth $reference.
	 */
	protected function setCurrentRow($reference = '')
	{
		// needs to be placed in the 1st method called
		// currently that's displayAddLabelLink in 1.5+
		// as the 1st action item added
		$current_row = array();
		foreach ($this->_list as $row)
			if ($reference == $row['reference'])
			{
				$current_row = $row;
				break;
			}

		if (!empty($current_row))
			$this->current_row = $current_row;
		// now we have it
	}

	protected function setRowContext($reference)
	{
		if (!Service::isPrestashop15plus())
			return;

		$order_bpost = PsOrderBpost::getByReference($reference);
		Shop::setContext(Shop::CONTEXT_SHOP, (int)$order_bpost->id_shop);
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

		// This is the 1st method called so store currentRow & set rowContext
		$this->setCurrentRow($reference);
		$this->setRowContext($reference);

		$tpl_vars = array(
			'action' => $this->l('Add label'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&addLabel'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
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
	public function displayCreateRetourLink($token = null, $reference = '')
	{
		// Do not display if retours are automatically generated
		if (empty($reference) || (bool)Configuration::get('BPOST_AUTO_RETOUR_LABEL'))
			return;

		$tpl_vars = array(
			'action' => $this->l('Create retour'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&createRetour'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
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
		$fields_list = $this->current_row;
		if (empty($reference) || empty($fields_list))
			return;

		$tpl_vars = array(
			'action' => $this->l('Mark treated'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&markTreated'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);

		// disable if labels are not PRINTED
		if (empty($fields_list['count_printed']))
			$tpl_vars['disabled'] = $this->l('Actions are only available for orders that are printed.');
		elseif ($this->bpost_treated_state == (int)$fields_list['current_state'])
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
		// Do not display if T&T mails are automatically sent
		if (empty($reference) || (bool)Configuration::get('BPOST_LABEL_TT_INTEGRATION'))
			return;

		$fields_list = $this->current_row;
		$tpl_vars = array(
			'action' => $this->l('Send Track & Trace e-mail'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&sendTTEmail'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);

		// disable if labels are not PRINTED
		if (empty($fields_list['count_printed']))
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
	public function displayViewLink($token = null, $reference = '')
	{
		if (empty($reference))
			return;

		$tpl_vars = array(
			'action' => $this->l('Open order'),
			'target' => '_blank',
		);

		$ps_order = new Order((int)Service::getOrderIDFromReference($reference));
		$token = Tools::getAdminTokenLite('AdminOrders');
		$tpl_vars['href'] = 'index.php?tab=AdminOrders&vieworder&id_order='.(int)$ps_order->id.'&token='.$token;

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
		$fields_list = $this->current_row;
		if (empty($reference))
			return;

		$tpl_vars = array(
			'action' => $this->l('Cancel order'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&cancel'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);

		// disable if labels have already been PRINTED
		if ((bool)$fields_list['count_printed'])
			$tpl_vars['disabled'] = $this->l('Only open orders can be cancelled.');

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}
}
