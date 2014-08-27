<?php

namespace ConsumErr\Sender;


use ConsumErr\Configuration;

interface ISender
{


    function __construct(Configuration $configuration);


    public function send($data);


}