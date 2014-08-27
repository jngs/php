<?php

namespace ConsumErr\Sender;


use ConsumErr\Configuration;

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


    public function send($data)
    {
        $header = array(
            'appId' => 'X-Consumerr-id: ' . $this->configuration->getId(),
            'appSecret' => 'X-Consumerr-secret: ' . $this->configuration->getToken(),
            'X-Consumerr-Encoding: base64',
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
    }

}

