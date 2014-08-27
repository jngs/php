<?php

namespace ConsumErr\Sender;


use ConsumErr\Configuration;

class PhpSender implements ISender
{
    /**
     * @var Configuration
     */
    private $config;


    function __construct(Configuration $config)
    {
        $this->config = $config;
    }


    public function send($data)
    {

        $data = http_build_query($data);
        $header = array(
            'type' => 'Content-type: application/x-www-form-urlencoded',
            'length' => 'Content-Length: ' . strlen($data),
            'appId' => 'X-Consumerr-id: ' . $this->config->getId(),
            'appSecret' => 'X-Consumerr-secret: ' . $this->config->getToken(),
            'X-Consumerr-Encoding: base64',
        );
        $req = @stream_context_create(
            array(
                'http' => array(
                    'method' => 'POST',
                    'header' => implode("\r\n", $header),
                    'content' => $data,
                )
            )
        );
        $fp = @fopen($this->config->getApiEndpoint(), 'rb', FALSE, $req);
        @fclose($fp);
    }

}