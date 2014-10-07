<?php

namespace Consumerr\Entities;


class Event
{

	/**
	 * @var integer
	 */
	private $datetime;

	/**
	 * @var string
	 */
	private $category;

	/**
	 * @var string
	 */
	private $action;

	/**
	 * @var string
	 */
	private $label = '';

	/**
	 * @var string
	 */
	private $value = '';


	function __construct($category, $action, $label = '', $value = '')
	{
		$this->datetime = time();
		$this->category = $category;
		$this->action = $action;
		$this->label = $label;
		$this->value = $value;
	}


	/**
	 * @return array
	 */
	public function __toArray()
	{
		return get_object_vars($this);
	}


	/**
	 * @param string $action
	 */
	public function setAction($action)
	{
		$this->action = $action;
	}


	/**
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}


	/**
	 * @param string $category
	 */
	public function setCategory($category)
	{
		$this->category = $category;
	}


	/**
	 * @return string
	 */
	public function getCategory()
	{
		return $this->category;
	}


	/**
	 * @return integer
	 */
	public function getDatetime()
	{
		return $this->datetime;
	}


	/**
	 * @param string $label
	 */
	public function setLabel($label)
	{
		$this->label = $label;
	}


	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}


	/**
	 * @param string $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}


	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

}
