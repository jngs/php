<?php

namespace Consumerr;


class DebugLogger
{

	private $file;

	public function __construct($file)
	{
		if ((file_exists($file) && !is_writable($file)) || (!is_writable(dirname($file)))) {
			throw new AssertionException("Log file $file is not writtable.");
		}
		$this->file = $file;
		self::log('----------------------');
	}


	public function log($message)
	{
		file_put_contents($this->file, "[" . date("Y-m-d H:i:s") . "] " . $message . "\n", FILE_APPEND | LOCK_EX);
	}


}