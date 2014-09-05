<?php

namespace ConsumErr;

class AssertionException extends \LogicException
{
	public static function isEmpty($field)
	{
		throw new self("You have to fill config option '$field'.");
	}
}