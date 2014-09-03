<?php

namespace ConsumErr\Sender;


use ConsumErr\Configuration;
use ConsumErr\ConsumErr;

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


    public function send($data, $encoding)
    {

        $data = http_build_query($data);
        $header = array(
            'type' => 'Content-type: application/x-www-form-urlencoded',
            'length' => 'Content-Length: ' . strlen($data),
            'appSecret' => 'X-Consumerr-secret: ' . $this->config->getToken(),
            'X-Consumerr-Encoding: '.$encoding,
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

        $prev = set_error_handler(function($severity, $message, $file) use (&$prev) {
            ConsumErr::log("Transmission error - ".trim($message));
            restore_error_handler();
            return;
        });
        $fp = @fopen($this->config->getApiEndpoint(), 'rb', FALSE, $req);
        @fclose($fp);
        restore_error_handler();
    }

}