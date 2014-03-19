<?php

namespace ConsumErr\Sender;



class CurlSender implements ISender
{

	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var string
	 */
	private $secret;

	/**
	 * @var string
	 */
	private $url;



	function __construct($id, $secret, $url)
	{
		$this->id = $id;
		$this->secret = $secret;
		$this->url = $url;
	}


	public function send($data)
	{
		$header = array(
			'appId' => 'X-Consumerr-id: ' . $this->id,
			'appSecret' => 'X-Consumerr-secret: ' . $this->secret,
		);
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_NOBODY, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);

		curl_exec($ch);
	}

}

