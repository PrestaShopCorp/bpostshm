<?php
/**
 * Bpost class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class EontechModBpost
{
	/* URL for the api (default) */
	const API_URL = 'https://api.bpost.be/services/shm';

	/* current version */
	const VERSION = '3.0.0';

	/**
	 * The account id
	 *
	 * @var string
	 */
	private $account_id;

	/**
	 * A cURL instance
	 *
	 * @var resource
	 */
	private $curl;

	/**
	 * The passPhrase
	 *
	 * @var string
	 */
	private $pass_phrase;

	/**
	 * The API_URL
	 *
	 * @var string
	 */
	private $api_url;

	/**
	 * The port to use.
	 *
	 * @var int
	 */
	private $port;

	/**
	 * The timeout
	 *
	 * @var int
	 */
	private $time_out = 30;

	/**
	 * The user agent
	 *
	 * @var string
	 */
	private $user_agent;

	/* class methods */
	/**
	 * Create Bpost instance
	 *
	 * @param string $account_id
	 * @param string $pass_phrase
	 */
	public function __construct($account_id, $pass_phrase, $api_url = '')
	{
		$this->account_id = (string)$account_id;
		$this->pass_phrase = (string)$pass_phrase;
		$this->api_url = empty($api_url) ? static::API_URL : (string)$api_url;
	}

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		if ($this->curl !== null)
		{
			curl_close($this->curl);
			$this->curl = null;
		}
	}

	/**
	 * Decode the response
	 *
	 * @param  SimpleXMLElement $item   The item to decode.
	 * @param  array			$return Just a placeholder.
	 * @return mixed
	 * @throws EontechModException
	 */
	private static function decodeResponse($item, $return = null)
	{
		$array_keys = array('barcode', 'orderLine', 'additionalInsurance', 'infoDistributed', 'infoPugo');
		$integer_keys = array('totalPrice');

		if ($item instanceof SimpleXMLElement)
			foreach ($item as $key => $value)
			{
				$attributes = (array)$value->attributes();

				if (!empty($attributes) && isset($attributes['@attributes']))
					$return[$key]['@attributes'] = $attributes['@attributes'];

				// empty
				if (isset($value['nil']) && (string)$value['nil'] === 'true')
					$return[$key] = null;
				// empty
				elseif (isset($value[0]) && (string)$value == '')
				{
					if (in_array($key, $array_keys))
						$return[$key][] = self::decodeResponse($value);
					else
						$return[$key] = self::decodeResponse($value, null, 1);
				}
				else
				{
					// arrays
					if (in_array($key, $array_keys))
						$return[$key][] = (string)$value;
					// booleans
					elseif ((string)$value == 'true')
						$return[$key] = true;
					elseif ((string)$value == 'false')
						$return[$key] = false;
					// integers
					elseif (in_array($key, $integer_keys))
						$return[$key] = (int)$value;
					// fallback to string
					else
						$return[$key] = (string)$value;
				}
			}
		else
			throw new EontechModException('Invalid item.');

		return $return;
	}

	/**
	 * Make the call
	 *
	 * @param  string $url	   The URL to call.
	 * @param  string $body	  The data to pass.
	 * @param  array  $headers   The headers to pass.
	 * @param  string $method	The HTTP-method to use.
	 * @param  bool   $expect_xml Do we expect XML?
	 * @return mixed
	 * @throws EontechModException
	 */
	protected function doCall($url, $body = null, $headers = array(), $method = 'GET', $expect_xml = true)
	{
		// build Authorization header
		$headers[] = 'Authorization: Basic '.$this->getAuthorizationHeader();

		// set options
		$options = array();
		$options[CURLOPT_URL] = $this->api_url.'/'.$this->account_id.$url;
		if ($this->getPort() != 0)
			$options[CURLOPT_PORT] = $this->getPort();
		$options[CURLOPT_USERAGENT] = $this->getUserAgent();
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_TIMEOUT] = (int)$this->getTimeOut();
		$options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
		$options[CURLOPT_HTTPHEADER] = $headers;
		$options[CURLOPT_SSL_VERIFYPEER] = false;

		if ($method == 'POST')
		{
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $body;
		}

		// init
		$this->curl = curl_init();

		// set options
		curl_setopt_array($this->curl, $options);

		// execute
		$response = curl_exec($this->curl);
		$headers = curl_getinfo($this->curl);

		// fetch errors
		$error_number = curl_errno($this->curl);
		$error_message = curl_error($this->curl);

		// error?
		if ($error_number != '')
			throw new EontechModException($error_message, $error_number);

		// valid HTTP-code
		if (!in_array($headers['http_code'], array(0, 200, 201)))
		{
			// convert into XML
			$xml = simplexml_load_string($response);

			// validate
			if ($xml !== false && (\Tools::substr($xml->getName(), 0, 7) == 'invalid'))
			{
				// message
				$message = (string)$xml->error;
				$code = isset($xml->code) ? (int)$xml->code : null;

				// throw exception
				throw new EontechModException($message, $code);
			}

			if ((isset($headers['content_type']) && substr_count($headers['content_type'], 'text/plain') > 0) || ($headers['http_code'] == '404'))
				$message = $response;
			else
				$message = 'Invalid response.';

			throw new EontechModException($message, $headers['http_code']);
		}

		// if we don't expect XML we can return the content here
		if (!$expect_xml)
			return $response;

		// convert into XML
		$xml = simplexml_load_string($response);

		// return the response
		return $xml;
	}

	/**
	 * Get the account id
	 *
	 * @return string
	 */
	public function getAccountId()
	{
		return $this->account_id;
	}

	/**
	 * Generate the secret string for the Authorization header
	 *
	 * @return string
	 */
	private function getAuthorizationHeader()
	{
		return base64_encode($this->account_id.':'.$this->pass_phrase);
	}

	/**
	 * Get the passPhrase
	 *
	 * @return string
	 */
	public function getPassPhrase()
	{
		return $this->pass_phrase;
	}

	/**
	 * Get the port
	 *
	 * @return int
	 */
	public function getPort()
	{
		return (int)$this->port;
	}

	/**
	 * Get the timeout that will be used
	 *
	 * @return int
	 */
	public function getTimeOut()
	{
		return (int)$this->time_out;
	}

	/**
	 * Get the useragent that will be used.
	 * Our version will be prepended to yours.
	 * It will look like: "PHP Bpost/<version> <your-user-agent>"
	 *
	 * @return string
	 */
	public function getUserAgent()
	{
		return (string)'PHP Bpost/'.self::VERSION.' '.$this->user_agent;
	}

	/**
	 * Set the timeout
	 * After this time the request will stop. You should handle any errors triggered by this.
	 *
	 * @param int $seconds The timeout in seconds.
	 */
	public function setTimeOut($seconds)
	{
		$this->time_out = (int)$seconds;
	}

	/**
	 * Set the user-agent for you application
	 * It will be appended to ours, the result will look like: "PHP Bpost/<version> <your-user-agent>"
	 *
	 * @param string $user_agent Your user-agent, it should look like <app-name>/<app-version>.
	 */
	public function setUserAgent($user_agent)
	{
		$this->user_agent = (string)$user_agent;
	}

	/* webservice methods */
/* orders */
	/**
	 * Creates a new order. If an order with the same orderReference already exists
	 *
	 * @param  EontechModBpostOrder $order
	 * @return bool
	 */
	public function createOrReplaceOrder(EontechModBpostOrder $order)
	{
		$url = '/orders';

		$document = new \DOMDocument('1.0', 'utf-8');
		$document->preserveWhiteSpace = false;
		$document->formatOutput = true;

		$document->appendChild(
			$order->toXML(
				$document,
				$this->account_id
			)
		);

		$headers = array(
			'Content-type: application/vnd.bpost.shm-order-v3+XML'
		);

		return (
			$this->doCall(
				$url,
				$document->saveXML(),
				$headers,
				'POST',
				false
			) == ''
		);
	}

	/**
	 * Fetch an order
	 *
	 * @param $reference
	 * @return EontechModBpostOrder
	 */
	public function fetchOrder($reference)
	{
		$url = '/orders/'.(string)$reference;

		$headers = array(
			'Accept: application/vnd.bpost.shm-order-v3+XML',
		);
		$xml = $this->doCall(
			$url,
			null,
			$headers
		);

		return EontechModBpostOrder::createFromXML($xml);
	}

	/**
	 * Modify the status for an order.
	 *
	 * @param string $reference	The reference for an order
	 * @param string $status	The new status, allowed values are: OPEN, PENDING, CANCELLED, COMPLETED or ON-HOLD
	 * @return bool
	 * @throws EontechModException
	 */
	public function modifyOrderStatus($reference, $status)
	{
		$status = \Tools::strtoupper($status);
		if (!in_array($status, EontechModBpostOrderBox::getPossibleStatusValues()))
			throw new EontechModException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', EontechModBpostOrderBox::getPossibleStatusValues())
				)
			);

		$url = '/orders/'.$reference;

		$document = new \DOMDocument('1.0', 'utf-8');
		$document->preserveWhiteSpace = false;
		$document->formatOutput = true;

		$order_update = $document->createElement('orderUpdate');
		$order_update->setAttribute('xmlns', 'http://schema.post.be/shm/deepintegration/v3/');
		$order_update->setAttribute('xmlns:xsi', '"http://www.w3.org/2001/XMLSchema-instance');
		$order_update->appendChild(
			$document->createElement('status', $status)
		);
		$document->appendChild($order_update);

		$headers = array(
			'Content-type: application/vnd.bpost.shm-orderUpdate-v3+XML'
		);

		return (
			$this->doCall(
				$url,
				$document->saveXML(),
				$headers,
				'POST',
				false
			) == ''
		);
	}

/* labels */
	/**
	 * Get the possible label formats
	 *
	 * @return array
	 */
	public static function getPossibleLabelFormatValues()
	{
		return array(
			'A4',
			'A6',
		);
	}

	/**
	 * Generic method to centralize handling of labels
	 *
	 * @param  string $url
	 * @param  string $format
	 * @param  bool   $with_return_labels
	 * @param  bool   $as_pdf
	 * @return array
	 * @throws EontechModException
	 */
	protected function getLabel($url, $format = 'A6', $with_return_labels = false, $as_pdf = false)
	{
		$format = \Tools::strtoupper($format);
		if (!in_array($format, self::getPossibleLabelFormatValues()))
			throw new EontechModException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleLabelFormatValues())
				)
			);

		$url .= '/labels/'.$format;
		if ($with_return_labels)
			$url .= '/withReturnLabels';

		if ($as_pdf)
			$headers = array(
				'Accept: application/vnd.bpost.shm-label-pdf-v3+XML',
			);
		else
			$headers = array(
				'Accept: application/vnd.bpost.shm-label-image-v3+XML',
			);

		$xml = $this->doCall(
			$url,
			null,
			$headers
		);

		$labels = array();

		if (isset($xml->label))
			foreach ($xml->label as $label)
				$labels[] = EontechModBpostLabel::createFromXML($label);

		return $labels;
	}

	/**
	 * Create the labels for all unprinted boxes in an order.
	 * The service will return labels for all unprinted boxes for that order.
	 * Boxes that were unprinted will get the status PRINTED, the boxes that
	 * had already been printed will remain the same.
	 *
	 * @param  string $reference		The reference for an order
	 * @param  string $format		   The desired format, allowed values are: A4, A6
	 * @param  bool   $with_return_labels Should return labels be returned?
	 * @param  bool   $as_pdf			Should we retrieve the PDF-version instead of PNG
	 * @return array
	 */
	public function createLabelForOrder($reference, $format = 'A6', $with_return_labels = false, $as_pdf = false)
	{
		$url = '/orders/'.(string)$reference;

		return $this->getLabel($url, $format, $with_return_labels, $as_pdf);
	}

	/**
	 * Create a label for a known barcode.
	 *
	 * @param  string $barcode		  The barcode of the parcel
	 * @param  string $format		   The desired format, allowed values are: A4, A6
	 * @param  bool   $with_return_labels Should return labels be returned?
	 * @param  bool   $as_pdf			Should we retrieve the PDF-version instead of PNG
	 * @return array
	 */
	public function createLabelForBox($barcode, $format = 'A6', $with_return_labels = false, $as_pdf = false)
	{
		$url = '/boxes/'.(string)$barcode;

		return $this->getLabel($url, $format, $with_return_labels, $as_pdf);
	}

	/**
	 * Create labels in bulk, according to the list of order references and the
	 * list of barcodes. When there is an order reference specified in the
	 * request, the service will return a label of every box of that order. If
	 * a certain box was not yet printed, it will have the status PRINTED
	 *
	 * @param  array  $references	   The references for the order
	 * @param  string $format		   The desired format, allowed values are: A4, A6
	 * @param  bool   $with_return_labels Should return labels be returned?
	 * @param  bool   $as_pdf			Should we retrieve the PDF-version instead of PNG
	 * @return array
	 * @throws EontechModException
	 */
	public function createLabelInBulkForOrders(array $references, $format = 'A6', $with_return_labels = false, $as_pdf = false)
	{
		$format = \Tools::strtoupper($format);
		if (!in_array($format, self::getPossibleLabelFormatValues()))
			throw new EontechModException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleLabelFormatValues())
				)
			);

		$url = '/labels/'.$format;

		if ($with_return_labels)
			$url .= '/withReturnLabels';

		if ($as_pdf)
			$headers = array(
				'Accept: application/vnd.bpost.shm-label-pdf-v3+XML',
			);
		else
			$headers = array(
				'Accept: application/vnd.bpost.shm-label-image-v3+XML',
			);
		$headers[] = 'Content-Type: application/vnd.bpost.shm-labelRequest-v3+XML';

		$document = new \DOMDocument('1.0', 'utf-8');
		$document->preserveWhiteSpace = false;
		$document->formatOutput = true;

		$batch_labels = $document->createElement('batchLabels');
		$batch_labels->setAttribute('xmlns', 'http://schema.post.be/shm/deepintegration/v3/');
		foreach ($references as $reference)
			$batch_labels->appendChild(
				$document->createElement('order', $reference)
			);
		$document->appendChild($batch_labels);

		$xml = $this->doCall(
			$url,
			$document->saveXML(),
			$headers,
			'POST'
		);

		$labels = array();

		if (isset($xml->label))
			foreach ($xml->label as $label)
				$labels[] = EontechModBpostLabel::createFromXML($label);

		return $labels;
	}
}
