<?php

namespace Consumerr\Sender;


use Consumerr\Configuration;

interface ISender
{


	function __construct(Configuration $configuration);


	public function send($data, $encoding);


}