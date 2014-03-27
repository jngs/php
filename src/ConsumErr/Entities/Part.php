<?php

namespace ConsumErr\Entities;


class Part
{

	/**
	 * @var float
	 */
	private $time;

	/**
	 * @var string
	 */
	private $name;


	function __construct($time, $name = '')
	{
		$this->name = $name;
		$this->time = $time;
	}


	/**
	 * @return array
	 */
	public function __toArray()
	{
		return get_object_vars($this);
	}


	/**
	 * @param array $values
	 */
	public function setValues($values)
	{
		foreach ($values as $var => $value) {
			$this->$var = $value;
		}
	}


	/**
	 * @param string $name
	 * @return Part
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @param float $time
	 * @return Part
	 */
	public function setTime($time)
	{
		$this->time = $time;
		return $this;
	}


	/**
	 * @return float
	 */
	public function getTime()
	{
		return $this->time;
	}

}
