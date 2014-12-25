<?php
/**
 * 2014 Stigmi
 *
 * PrestaShop v.1.4 back-office controller
 *
 * @author    Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit;

if (_PS_VERSION_ >= 1.5)
{
	require_once(_PS_MODULE_DIR_.'bpostshm/controllers/admin/AdminOrdersBpost.php');
	return;
}

require_once(_PS_MODULE_DIR_.'bpostshm/bpostshm.php');
require_once(_PS_MODULE_DIR_.'bpostshm/classes/Service.php');
class AdminOrdersBpost extends AdminTab
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

	public static $current_index;

	public function __construct()
	{
		$this->table = 'order_bpost';
		$this->className = 'AdminOrdersBpost';
		$this->lang = false;
		$this->explicitSelect = true;
		$this->deleted = false;
		$this->noLink = true;

		$this->view = true;
		$this->context = Context::getContext();
		self::$current_index = $_SERVER['SCRIPT_NAME'].(($tab = Tools::getValue('tab')) ? '?tab='.$tab : '');

		$iso_code = $this->context->language->iso_code;
		$iso_code = in_array($iso_code, array('de', 'fr', 'nl', 'en')) ? $iso_code : 'en';
		$this->tracking_params['oss_language'] = $iso_code;

		// cached current_row while building list
		// always false after display for any action
		$this->current_row = false;
		$this->bpost_treated_state = (int)Configuration::get('BPOST_ORDER_STATE_TREATED');

		$this->module = new BpostShm();
		$this->service = Service::getInstance($this->context);

		$this->_select = '
		a.`reference` as print,
		a.`reference` as t_t,
		a.`shm`,
		obl.`status` as lstatus,
		COUNT(obl.`id_order_bpost_label`) as count,
		SUM(obl.`barcode` IS NOT NULL) AS count_printed,
		SUM(obl.`is_retour` = 1) AS count_retours,
		SUM(obl.`has_retour` = 1) AS count_auto_retours 
		';

		$this->_join = '
		LEFT JOIN `'._DB_PREFIX_.'order_bpost_label` obl ON (a.`id_order_bpost` = obl.`id_order_bpost`)
		LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = SUBSTRING(a.`reference`, 8))
		LEFT JOIN `'._DB_PREFIX_.'carrier` c ON (c.`id_carrier` = o.`id_carrier`)
		';

		$this->_where = '
		AND obl.status IN("'.implode('", "', $this->statuses).'")
		AND a.current_state NOT IN('.implode(', ', $this->ps_order_state_rejects).')
		AND DATEDIFF(NOW(), a.date_add) <= 14
		';

		// SRG 23-09-14: Changed SQL to correctly simulate ^ps1.5 orders.current_state
/*		$this->_join = '
		LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = SUBSTRING(a.`reference`, 8))
		LEFT JOIN `'._DB_PREFIX_.'carrier` c ON (c.`id_carrier` = o.`id_carrier`)
		LEFT JOIN (
			SELECT oh.`id_order`, oh.`id_order_state` as current_state
			FROM `'._DB_PREFIX_.'order_history` oh
			INNER JOIN (
				SELECT max(`id_order_history`) as max_id, `id_order`
				FROM `'._DB_PREFIX_.'order_history`
				GROUP BY (`id_order`)
			) oh2
				ON 	oh.`id_order` = oh2.`id_order`
				AND oh2.max_id = oh.`id_order_history`
		) ocs ON ocs.`id_order` = o.`id_order`';

		$this->_where = '
		AND a.status IN("'.implode('", "', $this->statuses).'")
		AND DATEDIFF(NOW(), a.date_add) <= 14
		AND ocs.current_state IN('.implode(', ', $this->ps_order_states).', '
			.$this->bpost_treated_state.')';
*/

		$id_bpost_carriers = array_values($this->module->getIdCarriers());
		if ($references = Db::getInstance()->executeS('
			SELECT id_reference FROM `'._DB_PREFIX_.'carrier` WHERE id_carrier IN ('.implode(', ', $id_bpost_carriers).')'))
		{
			foreach ($references as $reference)
				$id_bpost_carriers[] = (int)$reference['id_reference'];
		}

		$this->_where .= '
		AND o.id_carrier IN ('.implode(', ', $id_bpost_carriers).')';

		$this->_group = 'GROUP BY(a.`reference`)';
		if (!Tools::getValue($this->table.'Orderby'))
			$this->_orderBy = 'o.id_order';
		if (!Tools::getValue($this->table.'Orderway'))
			$this->_orderWay = 'DESC';

		$this->fieldsDisplay = array(
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
			'lstatus' => array(
				'title' => $this->l('Status'),
				'width' => 60,
				'callback' => 'getCurrentStatus',
			),
			'date_add' => array(
				'title' => $this->l('Creation date'),
				'width' => 100,
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
				'class' => 'order_state',
				'align' => 'center',
				'search' => false,
			)
		);

		parent::__construct();
	}

	public function postProcess()
	{
		parent::postProcess();

		if (empty($this->_errors))
		{
			$reference 	= (string)Tools::getValue('reference');
			$response = array(
					// 'errors' => array(),
					// 'links' => array(),
				);

			if (Tools::getIsset('addLabel'.$this->table))
			{
				if (!$response = $this->service->addLabel($reference))
					$response['errors'] = 'Unable to add Label to order ['.$reference.'] Please check logs for errors.';

				$this->jsonEncode($response);
			}
			elseif (Tools::getIsset('createRetour'.$this->table))
			{
				if (!$response = $this->service->addLabel($reference, true))
					$response['errors'] = 'Unable to add Retour Label to order ['.$reference.'] Please check logs for errors.';

				$this->jsonEncode($response);
			}
			elseif (Tools::getIsset('printLabels'.$this->table))
			{
				$links = $this->service->printLabels($reference);
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
	 * @param mixed $content
	 */
	private function jsonEncode($content)
	{
		ob_clean();
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
				strtolower($this->l($state_name)),
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
		$history->changeIdOrderState((int)$order_state['id'], $ps_order->id);

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
		$content = $this->l('Your order').' '.$ps_order->id.' '.$this->l('can now be tracked here :')
			.' <a href="'.$tracking_url.'">'.$tracking_url.'</a>';

		$customer = new Customer($ps_order->id_customer);
		if (!Validate::isLoadedObject($customer))
			$this->_errors[] = Tools::displayError('The customer is invalid.');
		else
		{
			$message = new Message();
			$message->id_order = (int)$ps_order->id;
			$message->id_employee = (int)$this->context->employee->id;
			$message->message = $content;
			$message->private = false;

			try {
				if (!$message->add())
					$this->_errors[] = 'Ref '.$reference.': '.Tools::displayError('An error occurred while sending message.');
				elseif (Validate::isLoadedObject($customer = new Customer((int)$ps_order->id_customer)))
				{
					$order = new Order((int)$message->id_order);
					if (Validate::isLoadedObject($order))
					{
						$vars_tpl = array(
							'{lastname}' => $customer->lastname,
							'{firstname}' => $customer->firstname,
							'{id_order}' => $ps_order->id,
							'{order_name}' => sprintf('#%06d', (int)$message->id_order),
							'{message}' => $content,
						);

						Mail::Send((int)$order->id_lang, 'order_merchant_comment',
							Mail::l('New message regarding your order', (int)$order->id_lang), $vars_tpl, $customer->email,
							$customer->firstname.' '.$customer->lastname, null, null, null, null, _PS_MAIL_DIR_, true);
					}
				}

			} catch (Exception $e) {
				$this->_errors[] = $e->getMessage();
			}
		}

		if (!empty($this->errors))
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

		$this->tpl_list_vars = array_merge(
			$this->tpl_list_vars,
			array(
				'treated_status' =>
					Configuration::get('BPOST_ORDER_STATE_TREATED'),
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
		$context = Context::getContext();
		$context->cookie->{$this->table.'_pagination'} = 50;
		$context->cookie->update();

		// SRG 22-09-2014: Temporary sorting fix
		if (Tools::getValue($this->table.'Orderby'))
			$order_by = Tools::getValue($this->table.'Orderby');
		if (Tools::getValue($this->table.'Orderway'))
			$order_way = Tools::getValue($this->table.'Orderway');

		parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
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
				$labels[] = $this->service->printLabels($reference);

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
	 * @param string $status as stored
	 * @return string
	 */
	public function getCurrentStatus($status = 0)
	{
		$fields_list = $this->current_row;
		if (empty($status) || empty($fields_list))
			return;

		$count_printed = (int)$fields_list['count_printed'];
		$current_status = $status;
		$current_status .= $count_printed ? ' ('.$count_printed.')' : '';

		// if ((bool)Configuration::get('BPOST_LABEL_TT_UPDATE_ON_OPEN'))
		// {
		// 	$reference = $fields_list['reference'];
		// 	$current_state = (int)$fields_list['current_state'];
		// 	$current_status = ((int)Configuration::get('BPOST_ORDER_STATE_TREATED') === $current_state) ? $this->service->getOrderStatus($reference) : $status;
		// }

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
		$count_normal = (int)$count - $count_retours;

		$reduced_size = $count_normal ? 'font-size:10px;' : '';
		$plus = $count_normal ? ' +' : '';
		$disp_retours = '<span style="'.$reduced_size.'color:silver;">'.$plus.$count_retours.'R</span>';

		$current_count = $count_normal ?
			$count_normal.($count_retours ? $disp_retours : '') :
			$disp_retours;

		return $current_count;
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
		// currently that's getPrintIcon in 1.4
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

	/**
	 * @param string $reference
	 * @return string
	 */
	public function getPrintIcon($reference = '')
	{
		if (empty($reference))
			return;

		// This is the 1st method called so store currentRow
		$this->setCurrentRow($reference);

		return '<img class="print" src="'._MODULE_DIR_.'bpostshm/views/img/icons/print.png" data-labels="'
			.Tools::safeOutput(self::$current_index.'&reference='.$reference.'&printLabels'.$this->table.'&token='.$this->token).'"/>';
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
			'href' => Tools::safeOutput(self::$current_index.'&reference='.$reference.'&addLabel'.$this->table
					.'&token='.($token != null ? $token : $this->token)),
		);

		/*
		// Disable if retours auto-generation is OFF
		if (!(bool)Configuration::get('BPOST_AUTO_RETOUR_LABEL_'.$context_shop_id))
		{
			$pdf_dir = _PS_MODULE_DIR_.'bpostshm/pdf/'.$reference.'/retours';
			// disable if labels are not PRINTED
			if (is_dir($pdf_dir))
				$tpl_vars['disabled'] = $this->l('A retour has already been created.');
		}*/

		return $tpl_vars;
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
			'href' => Tools::safeOutput(self::$current_index.'&reference='.$reference.'&printLabels'.$this->table
					.'&token='.($token != null ? $token : $this->token))
		);

		return $tpl_vars;
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

		$fields_list = $this->current_row;
		$tpl_vars = array(
			'action' => $this->l('Create retour'),
			'href' => Tools::safeOutput(self::$current_index.'&reference='.$reference.'&createRetour'.$this->table
					.'&token='.($token != null ? $token : $this->token)),
		);

		return $tpl_vars;
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
			'href' => Tools::safeOutput(self::$current_index.'&reference='.$reference.'&markTreated'.$this->table
					.'&token='.($token != null ? $token : $this->token)),
		);

		// disable if labels are not PRINTED
		if (empty($fields_list['count_printed']))
			$tpl_vars['disabled'] = $this->l('Actions are only available for orders that are printed.');
		elseif ($this->bpost_treated_state == (int)$fields_list['current_state'])
			$tpl_vars['disabled'] = $this->l('Order is already treated.');

		return $tpl_vars;
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
			'href' => Tools::safeOutput(self::$current_index.'&reference='.$reference.'&sendTTEmail'.$this->table
					.'&token='.($token != null ? $token : $this->token)),
		);

		// disable if labels are not PRINTED
		if (empty($fields_list['count_printed']))
			$tpl_vars['disabled'] = $this->l('Actions are only available for orders that are printed.');

		return $tpl_vars;
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
		$tpl_vars['href'] = 'index.php?tab=AdminOrders&id_order='.(int)$ps_order->id.'&vieworder&token='.Tools::getAdminTokenLite('AdminOrders');
		// $tpl_vars['href'] = Tools::safeOutput(Tools::substr(self::$current_index, 0, -5)
		// 	.'&id_order='.(int)$ps_order->id
		// 	.'&vieworder&token='.Tools::getAdminTokenLite('AdminOrders'));

		return $tpl_vars;
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
			'href' => Tools::safeOutput(self::$current_index.'&reference='.$reference.'&cancel'.$this->table
					.'&token='.($token != null ? $token : $this->token)),
		);

		// disable if labels have already been PRINTED
		if ((bool)$fields_list['count_printed'])
			$tpl_vars['disabled'] = $this->l('Only open orders can be cancelled.');

		return $tpl_vars;
	}

	/* Experimental */

	protected function _displayViewLink($token = null, $id)
	{
		global $currentIndex;

		$_cacheLang['View'] = $this->l('View');

		$reference = $id;
		$actions = $this->getActions($id, $token);

		echo '<select class="actions">
				<option value="">-</option>';
			foreach ($actions as $action)
				echo '<option value="'.$action['href'].'" '
			.(isset($action['disabled']) ? 'disabled="disabled"' : '')
			.(isset($action['target']) ? ' data-target="'.$action['target'].'"' : '')
			.'>'.$action['action'].'</option>';

		echo '</select>';
	}

	public function getActions($reference = '', $token = null)
	{
		$actions = array();

		if ($add_label = $this->displayAddLabelLink($token, $reference))
			$actions[] = $add_label;
		if ($create_retour = $this->displayCreateRetourLink($token, $reference))
			$actions[] = $create_retour;
		if ($print_labels = $this->displayPrintLabelsLink($token, $reference))
			$actions[] = $print_labels;
		if ($mark_treated = $this->displayMarkTreatedLink($token, $reference))
			$actions[] = $mark_treated;
		if ($send_email = $this->displaySendTTEmailLink($token, $reference))
			$actions[] = $send_email;
		if ($view_order = $this->displayViewLink($token, $reference))
			$actions[] = $view_order;
		if ($cancel_order = $this->displaycancelLink($token, $reference))
			$actions[] = $cancel_order;

		return $actions;
	}

	public function displayList()
	{
		//echo 'Top of 1.4 list !!';
		echo '<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.1/jquery.min.js" type="text/javascript"></script>
				<script src="//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.pack.js" type="text/javascript"></script>
				<script src="'.__PS_BASE_URI__.'/modules/bpostshm/views/js/jquery.idTabs.min.js" type="text/javascript"></script>
				<link href="//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.css" type="text/css" rel="stylesheet" />
				<link href="'.__PS_BASE_URI__.'/modules/bpostshm/views/css/admin14.css" type="text/css" rel="stylesheet" />';

		parent::displayList();

		// echo 'Bottom of 1.4 list';
	}

	public function displayListFooter($token = null)
	{
		parent::displayListFooter($token);

		$treated_status = $this->bpost_treated_state;
		$reload_href = self::$current_index.'&token='.Tools::getAdminTokenLite('AdminOrdersBpost');
		require_once _PS_MODULE_DIR_.'bpostshm/views/templates/admin/orders_bpost/helpers/list/list_footer14.tpl';
	}

	/**
	 * @author Serge <serge@stigmi.eu>
	 * Time for a proper 1.4 revamp :)
	 * Display list header (filtering, pagination and column names)
	 *
	 * @global string $currentIndex Current URL in order to keep current Tab
	 */
	public function displayListHeader($token = null)
	{
		global $currentIndex, $cookie;
		$isCms = false;
		if (preg_match('/cms/Ui', $this->identifier))
			$isCms = true;
		$id_cat = Tools::getValue('id_'.($isCms ? 'cms_' : '').'category');

		if (!isset($token) || empty($token))
			$token = $this->token;

		/* Determine total page number */
		$totalPages = ceil($this->_listTotal / Tools::getValue('pagination', (isset($cookie->{$this->table.'_pagination'}) ? $cookie->{$this->table.'_pagination'} : $this->_pagination[0])));
		if (!$totalPages) $totalPages = 1;

		echo '<a name="'.$this->table.'">&nbsp;</a>';
		echo '<form method="post" action="'.$currentIndex;
		if (Tools::getIsset($this->identifier))
			echo '&'.$this->identifier.'='.(int)(Tools::getValue($this->identifier));
		echo '&token='.$token;
		if (Tools::getIsset($this->table.'Orderby'))
			echo '&'.$this->table.'Orderby='.urlencode($this->_orderBy).'&'.$this->table.'Orderway='.urlencode(strtolower($this->_orderWay));
		echo '#'.$this->table.'" class="form">
		<input type="hidden" id="submitFilter'.$this->table.'" name="submitFilter'.$this->table.'" value="0">
		<table>
			<tr>
				<td style="vertical-align: bottom;">
					<span style="float: left;">';
		if ($this->table != 'order_return_state')
		{
			/* Determine current page number */
			$page = (int)(Tools::getValue('submitFilter'.$this->table));
			if (!$page) $page = 1;
			if ($page > 1)
				echo '
							<input type="image" src="../img/admin/list-prev2.gif" onclick="getE(\'submitFilter'.$this->table.'\').value=1"/>
							&nbsp; <input type="image" src="../img/admin/list-prev.gif" onclick="getE(\'submitFilter'.$this->table.'\').value='.($page - 1).'"/> ';
			echo $this->l('Page').' <b>'.$page.'</b> / '.$totalPages;
			if ($page < $totalPages)
				echo '
							<input type="image" src="../img/admin/list-next.gif" onclick="getE(\'submitFilter'.$this->table.'\').value='.($page + 1).'"/>
							 &nbsp;<input type="image" src="../img/admin/list-next2.gif" onclick="getE(\'submitFilter'.$this->table.'\').value='.$totalPages.'"/>';
			echo '			| '.$this->l('Display').'
							<select name="pagination">';
			/* Choose number of results per page */
			$selectedPagination = Tools::getValue('pagination', (isset($cookie->{$this->table.'_pagination'}) ? $cookie->{$this->table.'_pagination'} : null));
			foreach ($this->_pagination as $value)
				echo '<option value="'.(int)($value).'"'.($selectedPagination == $value ? ' selected="selected"' : (($selectedPagination == null && $value == $this->_pagination[1]) ? ' selected="selected2"' : '')).'>'.(int)($value).'</option>';
			echo '
							</select>
							/ '.(int)($this->_listTotal).' '.$this->l('result(s)').'
						</span>
					';
		}
		echo '
					<span style="float: right;">
						<input type="submit" name="submitReset'.$this->table.'" value="'.$this->l('Reset').'" class="button" />
						<input type="submit" id="submitFilterButton_'.$this->table.'" name="submitFilter" value="'.$this->l('Filter').'" class="button" />
					</span>
					<span class="clear"></span>
				</td>
			</tr>
			<tr>
				<td>';

		/* Display column names and arrows for ordering (ASC, DESC) */
		if (array_key_exists($this->identifier, $this->identifiersDnd) && $this->_orderBy == 'position')
		{
			echo '
			<script type="text/javascript" src="../js/jquery/jquery.tablednd_0_5.js"></script>
			<script type="text/javascript">
				var token = \''.($token != null ? $token : $this->token).'\';
				var come_from = \''.$this->table.'\';
				var alternate = \''.($this->_orderWay == 'DESC' ? '1' : '0' ).'\';
			</script>
			<script type="text/javascript" src="../js/admin-dnd.js"></script>
			';
		}
		echo '<table'.(array_key_exists($this->identifier, $this->identifiersDnd) ? ' id="'.(((int)(Tools::getValue($this->identifiersDnd[$this->identifier], 1))) ? substr($this->identifier, 3, strlen($this->identifier)) : '').'"' : '' ).' class="table'.((array_key_exists($this->identifier, $this->identifiersDnd) && ($this->_listTotal >= 2 && $this->_orderBy != 'position ' && $this->_orderWay != 'DESC')) ? ' tableDnD'  : '' ).'" cellpadding="0" cellspacing="0">
			<thead>
				<tr class="nodrag nodrop">
					<th>';
		if ($this->delete)
			echo '		<input type="checkbox" name="checkme" class="noborder" onclick="checkDelBoxes(this.form, \''.$this->table.'Box[]\', this.checked)" />';
		echo '		</th>';
		foreach ($this->fieldsDisplay as $key => $params)
		{
			echo '	<th '.(isset($params['widthColumn']) ? 'style="width: '.$params['widthColumn'].'px"' : '').'>'.$params['title'];
			if (!isset($params['orderby']) || $params['orderby'])
			{
				// Cleaning links
				if (Tools::getValue($this->table.'Orderby') && Tools::getValue($this->table.'Orderway'))
					$currentIndex = preg_replace('/&'.$this->table.'Orderby=([a-z _]*)&'.$this->table.'Orderway=([a-z]*)/i', '', $currentIndex);
				if ($this->_listTotal >= 2)
				{
					echo '	<br />
							<a href="'.$currentIndex.'&'.$this->identifier.'='.(int)$id_cat.'&'.$this->table.'Orderby='.urlencode($key).'&'.$this->table.'Orderway=desc&token='.$token.'"><img border="0" src="../img/admin/down'.((isset($this->_orderBy) && ($key == $this->_orderBy) && ($this->_orderWay == 'DESC')) ? '_d' : '').'.gif" /></a>
							<a href="'.$currentIndex.'&'.$this->identifier.'='.(int)$id_cat.'&'.$this->table.'Orderby='.urlencode($key).'&'.$this->table.'Orderway=asc&token='.$token.'"><img border="0" src="../img/admin/up'.((isset($this->_orderBy) && ($key == $this->_orderBy) && ($this->_orderWay == 'ASC')) ? '_d' : '').'.gif" /></a>';
				}
			}
			echo '	</th>';
		}

		/* Check if object can be modified, deleted or detailed */
		if ($this->edit || $this->delete || ($this->view && $this->view !== 'noActionColumn'))
			echo '	<th style="width: 52px;text-align: center;">'.$this->l('Actions').'</th>';
		echo '	</tr>
				<tr class="nodrag nodrop" style="height: 35px;">
					<td class="center">';
		if ($this->delete)
			echo '		--';
		echo '		</td>';

		/* Javascript hack in order to catch ENTER keypress event */
		$keyPress = 'onkeypress="formSubmit(event, \'submitFilterButton_'.$this->table.'\');"';

		/* Filters (input, select, date or bool) */
		foreach ($this->fieldsDisplay as $key => $params)
		{
			$width = (isset($params['width']) ? ' style="width: '.(int)($params['width']).'px;"' : '');
			echo '<td'.(isset($params['align']) ? ' class="'.$params['align'].'"' : '').'>';
			if (!isset($params['type']))
				$params['type'] = 'text';

			$value = Tools::getValue($this->table.'Filter_'.(array_key_exists('filter_key', $params) ? $params['filter_key'] : $key));
			if (isset($params['search']) && !$params['search'])
			{
				// echo '--</td>';
				echo '</td>';
				continue;
			}
			switch ($params['type'])
			{
				case 'bool':
					echo '
					<select name="'.$this->table.'Filter_'.$key.'">
						<option value="">--</option>
						<option value="1"'.($value == 1 ? ' selected="selected"' : '').'>'.$this->l('Yes').'</option>
						<option value="0"'.(($value == 0 && $value != '') ? ' selected="selected"' : '').'>'.$this->l('No').'</option>
					</select>';
					break;

				case 'date':
				case 'datetime':
					if (is_string($value))
						$value = unserialize($value);
					if (!Validate::isCleanHtml($value[0]) || !Validate::isCleanHtml($value[1]))
						$value = '';
					$name = $this->table.'Filter_'.(isset($params['filter_key']) ? $params['filter_key'] : $key);
					$nameId = str_replace('!', '__', $name);
					includeDatepicker(array($nameId.'_0', $nameId.'_1'));
					echo $this->l('From').' <input type="text" id="'.$nameId.'_0" name="'.$name.'[0]" value="'.(isset($value[0]) ? $value[0] : '').'"'.$width.' '.$keyPress.' /><br />
					'.$this->l('To').' <input type="text" id="'.$nameId.'_1" name="'.$name.'[1]" value="'.(isset($value[1]) ? $value[1] : '').'"'.$width.' '.$keyPress.' />';
					break;

				case 'select':

					if (isset($params['filter_key']))
					{
						echo '<select onchange="$(\'#submitFilter'.$this->table.'\').focus();$(\'#submitFilter'.$this->table.'\').click();" name="'.$this->table.'Filter_'.$params['filter_key'].'" '.(isset($params['width']) ? 'style="width: '.$params['width'].'px"' : '').'>
								<option value=""'.(($value == 0 && $value != '') ? ' selected="selected"' : '').'>--</option>';
						if (isset($params['select']) && is_array($params['select']))
							foreach ($params['select'] as $optionValue => $optionDisplay)
								echo '<option value="'.$optionValue.'"'.((isset($_POST[$this->table.'Filter_'.$params['filter_key']]) && Tools::getValue($this->table.'Filter_'.$params['filter_key']) == $optionValue && Tools::getValue($this->table.'Filter_'.$params['filter_key']) != '') ? ' selected="selected"' : '').'>'.$optionDisplay.'</option>';
						echo '</select>';
						break;
					}

				case 'text':
				default:
					if (!Validate::isCleanHtml($value))
							$value = '';
					echo '<input type="text" name="'.$this->table.'Filter_'.(isset($params['filter_key']) ? $params['filter_key'] : $key).'" value="'.htmlentities($value, ENT_COMPAT, 'UTF-8').'"'.$width.' '.$keyPress.' />';
			}
			echo '</td>';
		}

		if ($this->edit || $this->delete || ($this->view && $this->view !== 'noActionColumn'))
			echo '<td class="center">--</td>';

		echo '</tr>
			</thead>';
	}

	public function displayListContent($token = null)
	{
		/* Display results in a table
		 *
		 * align  : determine value alignment
		 * prefix : displayed before value
		 * suffix : displayed after value
		 * image  : object image
		 * icon   : icon determined by values
		 * active : allow to toggle status
		 */

		global $currentIndex, $cookie;
		$currency = new Currency(_PS_CURRENCY_DEFAULT_);

		$id_category = 1; // default categ

		$irow = 0;
		if ($this->_list && isset($this->fieldsDisplay['position']))
		{
			$positions = array_map(create_function('$elem', 'return (int)($elem[\'position\']);'), $this->_list);
			sort($positions);
		}
		if ($this->_list)
		{
			$isCms = false;
			if (preg_match('/cms/Ui', $this->identifier))
				$isCms = true;
			$keyToGet = 'id_'.($isCms ? 'cms_' : '').'category'.(in_array($this->identifier, array('id_category', 'id_cms_category')) ? '_parent' : '');
			foreach ($this->_list as $tr)
			{
				$id = $tr[$this->identifier];
				echo '<tr'.(array_key_exists($this->identifier, $this->identifiersDnd) ? ' id="tr_'.(($id_category = (int)(Tools::getValue('id_'.($isCms ? 'cms_' : '').'category', '1'))) ? $id_category : '').'_'.$id.'_'.$tr['position'].'"' : '').($irow++ % 2 ? ' class="alt_row"' : '').' '.((isset($tr['color']) && $this->colorOnBackground) ? 'style="background-color: '.$tr['color'].'"' : '').'>
							<td class="center">';
				if ($this->delete && (!isset($this->_listSkipDelete) || !in_array($id, $this->_listSkipDelete)))
					echo '<input type="checkbox" name="'.$this->table.'Box[]" value="'.$id.'" class="noborder" />';
				echo '</td>';
				foreach ($this->fieldsDisplay as $key => $params)
				{
					$tmp = explode('!', $key);
					$key = isset($tmp[1]) ? $tmp[1] : $tmp[0];
					// Serge start
					$class = '';
					$class .= isset($params['align']) ? $params['align'] : '';
					$class .= isset($params['class']) ? ' '.$params['class'] : '';

					echo '
					<td '.(isset($params['position']) ? ' id="td_'.(isset($id_category) && $id_category ? $id_category : 0).'_'.$id.'"' : '').
					' class="'.((!isset($this->noLink) || !$this->noLink) ? 'pointer' : '').
					((isset($params['position']) && $this->_orderBy == 'position')? ' dragHandle' : '').
					//(isset($params['align']) ? ' '.$params['align'] : '').'" ';
					(!empty($class) ? ' '.$class : '').'" ';
					// Serge end
					if (!isset($params['position']) && (!isset($this->noLink) || !$this->noLink))
						echo ' onclick="document.location = \''.$currentIndex.'&'.$this->identifier.'='.$id.($this->view? '&view' : '&update').$this->table.'&token='.($token != null ? $token : $this->token).'\'">'.(isset($params['prefix']) ? $params['prefix'] : '');
					else
						echo '>';
					if (isset($params['active']) && isset($tr[$key]))
						$this->_displayEnableLink($token, $id, $tr[$key], $params['active'], Tools::getValue('id_category'), Tools::getValue('id_product'));
					elseif (isset($params['activeVisu']) && isset($tr[$key]))
						echo '<img src="../img/admin/'.($tr[$key] ? 'enabled.gif' : 'disabled.gif').'"
						alt="'.($tr[$key] ? $this->l('Enabled') : $this->l('Disabled')).'" title="'.($tr[$key] ? $this->l('Enabled') : $this->l('Disabled')).'" />';
					elseif (isset($params['position']))
					{
						if ($this->_orderBy == 'position' && $this->_orderWay != 'DESC')
						{
							echo '<a'.(!($tr[$key] != $positions[count($positions) - 1]) ? ' style="display: none;"' : '').' href="'.$currentIndex.
									'&'.$keyToGet.'='.(int)($id_category).'&'.$this->identifiersDnd[$this->identifier].'='.$id.'
									&way=1&position='.(int)($tr['position'] + 1).'&token='.($token != null ? $token : $this->token).'">
									<img src="../img/admin/'.($this->_orderWay == 'ASC' ? 'down' : 'up').'.gif"
									alt="'.$this->l('Down').'" title="'.$this->l('Down').'" /></a>';

							echo '<a'.(!($tr[$key] != $positions[0]) ? ' style="display: none;"' : '').' href="'.$currentIndex.
									'&'.$keyToGet.'='.(int)($id_category).'&'.$this->identifiersDnd[$this->identifier].'='.$id.'
									&way=0&position='.(int)($tr['position'] - 1).'&token='.($token != null ? $token : $this->token).'">
									<img src="../img/admin/'.($this->_orderWay == 'ASC' ? 'up' : 'down').'.gif"
									alt="'.$this->l('Up').'" title="'.$this->l('Up').'" /></a>';
						}
						else
							echo (int)($tr[$key] + 1);
					}
					elseif (isset($params['image']))
					{
						// item_id is the product id in a product image context, else it is the image id.
						$item_id = isset($params['image_id']) ? $tr[$params['image_id']] : $id;
						// If it's a product image
						if (isset($tr['id_image']))
						{
							$image = new Image((int)$tr['id_image']);
							$path_to_image = _PS_IMG_DIR_.$params['image'].'/'.$image->getExistingImgPath().'.'.$this->imageType;
						}
						else
							$path_to_image = _PS_IMG_DIR_.$params['image'].'/'.$item_id.(isset($tr['id_image']) ? '-'.(int)($tr['id_image']) : '').'.'.$this->imageType;

						echo cacheImage($path_to_image, $this->table.'_mini_'.$item_id.'.'.$this->imageType, 45, $this->imageType);
					}
					elseif (isset($params['icon']) && (isset($params['icon'][$tr[$key]]) || isset($params['icon']['default'])))
						echo '<img src="../img/admin/'.(isset($params['icon'][$tr[$key]]) ? $params['icon'][$tr[$key]] : $params['icon']['default'].'" alt="'.$tr[$key]).'" title="'.$tr[$key].'" />';
					elseif (isset($params['price']))
						echo Tools::displayPrice($tr[$key], (isset($params['currency']) ? Currency::getCurrencyInstance((int)($tr['id_currency'])) : $currency), false);
					elseif (isset($params['float']))
						echo rtrim(rtrim($tr[$key], '0'), '.');
					elseif (isset($params['type']) && $params['type'] == 'date')
						echo Tools::displayDate($tr[$key], (int)$cookie->id_lang);
					elseif (isset($params['type']) && $params['type'] == 'datetime')
						echo Tools::displayDate($tr[$key], (int)$cookie->id_lang, true);
					elseif (isset($tr[$key]))
					{
						$echo = ($key == 'price' ? round($tr[$key], 2) : isset($params['maxlength']) ? Tools::substr($tr[$key], 0, $params['maxlength']).'...' : $tr[$key]);
						echo isset($params['callback']) ? call_user_func_array(array($this->className, $params['callback']), array($echo, $tr)) : $echo;
					}
					else
						echo '--';

					echo (isset($params['suffix']) ? $params['suffix'] : '').
					'</td>';
				}

				if ($this->edit || $this->delete || ($this->view && $this->view !== 'noActionColumn'))
				{
					echo '<td class="center" style="white-space: nowrap;">';
					if ($this->view)
						$this->_displayViewLink($token, $id);
					if ($this->edit)
						$this->_displayEditLink($token, $id);
					if ($this->delete && (!isset($this->_listSkipDelete) || !in_array($id, $this->_listSkipDelete)))
						$this->_displayDeleteLink($token, $id);
					if ($this->duplicate)
						$this->_displayDuplicate($token, $id);
					echo '</td>';
				}
				echo '</tr>';
			}
		}
	}

}
