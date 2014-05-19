<?php

namespace ConsumErr\Sender;


class PhpSender implements ISender
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

        $data = http_build_query($data);
        $header = array(
            'type' => 'Content-type: application/x-www-form-urlencoded',
            'length' => 'Content-Length: ' . strlen($data),
            'appId' => 'X-Consumerr-id: ' . $this->id,
            'appSecret' => 'X-Consumerr-secret: ' . $this->secret,
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
        $fp = @fopen($this->url, 'rb', FALSE, $req);
        @fclose($fp);
    }

}