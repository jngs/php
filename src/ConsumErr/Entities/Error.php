<?php

namespace ConsumErr\Entities;


class Error
{
	/**
	 * @var string
	 */
	private $exception;

	/**
	 * @var string
	 */
	private $message;

	/**
	 * @var integer
	 */
	private $code;

	/**
	 * @var string
	 */
	private $file;

	/**
	 * @var integer
	 */
	private $line;

	/**
	 * @var string
	 */
	private $trace;

	/**
	 * @var integer
	 */
	private $severity;

	/**
	 * @var array
	 */
	private $data = array();


	/**
	 * @param \Exception $exception
	 */
	public function __construct(\Exception $exception)
	{
		$this->exception = get_class($exception);
		$this->message = $exception->getMessage();
		$this->code = $exception->getCode();
		$this->file = $exception->getFile();
		$this->line = $exception->getLine();
		$this->trace = $exception->getTraceAsString();

		if ($exception instanceof \ErrorException) {
			$this->severity = $exception->getSeverity();
		}
	}


	/**
	 * @return array
	 */
	public function __toArray()
	{
		return get_object_vars($this);
	}


	/**
	 * @param integer $code
	 */
	public function setCode($code)
	{
		$this->code = $code;
	}


	/**
	 * @return integer
	 */
	public function getCode()
	{
		return $this->code;
	}


	/**
	 * @param string $exception
	 */
	public function setException($exception)
	{
		$this->exception = $exception;
	}


	/**
	 * @return string
	 */
	public function getException()
	{
		return $this->exception;
	}


	/**
	 * @param string $file
	 */
	public function setFile($file)
	{
		$this->file = $file;
	}


	/**
	 * @return string
	 */
	public function getFile()
	{
		return $this->file;
	}


	/**
	 * @param integer $line
	 */
	public function setLine($line)
	{
		$this->line = $line;
	}


	/**
	 * @return integer
	 */
	public function getLine()
	{
		return $this->line;
	}


	/**
	 * @param string $message
	 */
	public function setMessage($message)
	{
		$this->message = $message;
	}


	/**
	 * @return string
	 */
	public function getMessage()
	{
		return $this->message;
	}


	/**
	 * @param integer $severity
	 */
	public function setSeverity($severity)
	{
		$this->severity = $severity;
	}


	/**
	 * @return integer
	 */
	public function getSeverity()
	{
		return $this->severity;
	}


	/**
	 * @param string $trace
	 */
	public function setTrace($trace)
	{
		$this->trace = $trace;
	}


	/**
	 * @return string
	 */
	public function getTrace()
	{
		return $this->trace;
	}


	/**
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	public function addData($key, $value)
	{
		$this->data[$key] = $value;

		return $this;
	}


	/**
	 * @param $key
	 * @return $this
	 */
	public function removeData($key)
	{
		unset($this->data[$key]);

		return $this;
	}

}
