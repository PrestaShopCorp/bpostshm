<?php
/**
 * 2014 Stigmi
 *
 * bpost Shipping Manager
 *
 * This controller is used by PrestaShop 1.4 shops
 *
 * @author    Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ParentOrderController extends ParentOrderControllerCore
{
	public function setMedia()
	{
		FrontController::setMedia();

		// Adding CSS style sheet
		Tools::addCSS(_THEME_CSS_DIR_.'addresses.css');

		// Adding JS files
		Tools::addJS(_THEME_JS_DIR_.'tools.js');
		if ((Configuration::get('PS_ORDER_PROCESS_TYPE') == 0 && Tools::getValue('step') == 1) || Configuration::get('PS_ORDER_PROCESS_TYPE') == 1 || Tools::getValue('step') == 2)
			Tools::addJS(_THEME_JS_DIR_.'order-address.js');
		if ((int)(Configuration::get('PS_BLOCK_CART_AJAX')) OR Configuration::get('PS_ORDER_PROCESS_TYPE') == 1)
		{
			Tools::addJS(_THEME_JS_DIR_.'cart-summary.js');
			Tools::addJS(_PS_JS_DIR_.'jquery/jquery-typewatch.pack.js');
		}

		// Add fancybox v2+, get responsive !
		Tools::addCSS((_PS_SSL_ENABLED_ ? 'https://' : 'http://').'//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.css', 'screen');
		Tools::addJS((_PS_SSL_ENABLED_ ? 'https://' : 'http://').'//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.pack.js');
	}
}

