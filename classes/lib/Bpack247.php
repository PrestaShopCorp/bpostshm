<?php
/**
 * bPost Bpack24/7 class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class TijsVerkoyenBpostBpack247
{
	/* URL for the api */
	const API_URL = 'http://www.bpack247.be/BpostRegistrationWebserviceREST/servicecontroller.svc';

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

	/**
	 * Make the call
	 *
	 * @param  string $url	The URL to call.
	 * @param  string $body   The data to pass.
	 * @param  string $method The HTTP-method to use.
	 * @return mixed
	 * @throws TijsVerkoyenBpostException
	 */
	private function doCall($url, $body = null, $method = 'GET')
	{
		// build Authorization header
		$headers = array('Authorization: Basic '.$this->getAuthorizationHeader());

		// set options
		$options = array();
		$options[CURLOPT_URL] = self::API_URL.$url;
		$options[CURLOPT_USERAGENT] = $this->getUserAgent();
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_TIMEOUT] = (int)$this->getTimeOut();
		$options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
		
		if ($method == 'POST')
		{
			// SRG Correction	
			$headers[] = 'Content-Type: application/xml';
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $body;
		}

		$options[CURLOPT_HTTPHEADER] = $headers;

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
			throw new TijsVerkoyenBpostException($error_message, $error_number);

		// valid HTTP-code
		if (!in_array($headers['http_code'], array(0, 200)))
		{
			$xml = @simplexml_load_string($response);

			if ($xml !== false && ($xml->getName() == 'businessException' || $xml->getName() == 'validationException'))
			{
				//$message = (string)$xml->message;
				//$code = isset($xml->code) ? (int)$xml->code : null;
			// SRG Correction	
				$message = (string)$xml->Message;
				$code = isset($xml->Code) ? (int)$xml->Code : null;
				throw new TijsVerkoyenBpostException($message, $code);
			}

			throw new TijsVerkoyenBpostException('Invalid response.', $headers['http_code']);
		}

		// convert into XML
		$xml = simplexml_load_string($response);

		// validate
		if ($xml->getName() == 'businessException')
		{
			$message = (string)$xml->message;
			$code = (string)$xml->code;
			throw new TijsVerkoyenBpostException($message, $code);
		}

		// return the response
		return $xml;
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
		return (string)'PHP Bpost Bpack247/'.self::VERSION.' '.$this->user_agent;
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

	/**
	 * Create Bpost instance
	 *
	 * @param string $account_id
	 * @param string $pass_phrase
	 */
	public function __construct($account_id, $pass_phrase)
	{
		$this->account_id = (string)$account_id;
		$this->pass_phrase = (string)$pass_phrase;
	}

	/* webservice methods */
	public function createMember(TijsVerkoyenBpostBpack247Customer $customer)
	{
		$url = '/customer';

		$document = new \DOMDocument('1.0', 'utf-8');
		$document->preserveWhiteSpace = false;
		$document->formatOutput = true;

		$document->appendChild(
			$customer->toXML(
				$document
			)
		);

		return $this->doCall(
			$url,
			$document->saveXML(),
			'POST'
		);
	}

	/**
	 * Retrieve member information
	 *
	 * @param  string   $id
	 * @param  boolean  $as_xml [optional]
	 * @return TijsVerkoyenBpostBpack247Customer
	 */
	public function getMember($id, $as_xml = false)
	{
		$xml = $this->doCall(
			'/customer/'.$id
		);

		if ($as_xml)
			return $xml;
		
		return TijsVerkoyenBpostBpack247Customer::createFromXML($xml);
	}
}
