<?php

namespace ConsumErr\Sender;



interface ISender
{


	function __construct($id, $secret, $url);


	public function send($data);


}