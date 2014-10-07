<?php

namespace Consumerr\Sender;


use Consumerr\Configuration;
use Consumerr\Consumerr;

class CurlSender implements ISender
{
	/**
	 * @var Configuration
	 */
	private $configuration;


	function __construct(Configuration $configuration)
	{
		$this->configuration = $configuration;
	}


	public function send($data, $encoding)
	{
		$header = array(
			'appSecret' => 'X-Consumerr-secret: ' . $this->configuration->getToken(),
			'X-Consumerr-Encoding: ' . $encoding,
		);
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->configuration->getApiEndpoint());
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_NOBODY, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_TIMEOUT, 2);

		curl_exec($ch);
		if ($this->configuration->getLogFile()) { //logging enabled
			if (curl_errno($ch) !== 0) {
				Consumerr::log("Transmission error - " . curl_error($ch));
			}
			$info = curl_getinfo($ch);
			if ($info['http_code'] != 200) {
				Consumerr::log("Transmission error - API returned HTTP " . $info['http_code']);
			}
		}
		@curl_close($ch);
	}

}

