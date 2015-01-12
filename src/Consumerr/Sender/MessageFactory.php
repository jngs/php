<?php


namespace Consumerr\Sender;


use Consumerr\Consumerr;
use Consumerr\Entities\Access;

class MessageFactory
{

	private $access;

	public function __construct(Access $access)
	{
		$this->access = $access;
	}


	public function send()
	{
		$this->access->setMemory(function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : NULL);
		$this->access->setTime(-1 * $this->access->getTime() + microtime(TRUE));

		list($accessData, $encoding) = $this->encodeData((string)$this->access);

		Consumerr::log("Shutdown - data encoded with $encoding, data length " . strlen($accessData));
		$sender = Consumerr::getConfiguration()->getSenderInstance();
		Consumerr::log("Shutdown - will use " . get_class($sender) . " for sending data.");
		$sender->send(array($accessData), $encoding);
		Consumerr::log("Shutdown complete.");
	}

	private static function encodeData($param)
	{
		$encoding = 'plain';
		if (Consumerr::getConfiguration()->isCompressionEnabled()) {
			$encoding = 'gzip';
			$param = gzcompress($param);
		}

		return array(base64_encode($param), $encoding);
	}

}