<?php
/**
 * 2014 Stigmi
 *
 * bpost Shipping Manager
 *
 * Allow your customers to choose their preferrred delivery method: delivery at home or the office, at a pick-up location or in a bpack 24/7 parcel
 * machine.
 *
 * @author    Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class OrderOpcController extends OrderOpcControllerCore
{
	protected function _assignPayment()
	{
		$top_payment = Hook::exec('topPayment');

		if (!empty($top_payment))
		{
			$hook_top_payment = '';
			$hook_payment = $top_payment;
		}
		else
		{
			$hook_top_payment = ($this->isLogged ? Hook::exec('displayPaymentTop') : '');
			$hook_payment = $this->_getPaymentMethods();
		}

		$this->context->smarty->assign(array(
			'HOOK_TOP_PAYMENT' => $hook_top_payment,
			'HOOK_PAYMENT' => $hook_payment,
		));
	}
}