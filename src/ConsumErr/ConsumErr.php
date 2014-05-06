<?php

namespace ConsumErr;

use ConsumErr\Entities;

/**/ class_alias('ConsumErr\ConsumErr', 'ConsumErr'); /**/


class ConsumErr
{

	private static $options = array(
		'id' => '',
		'secret' => '',
		'url' => 'http://service.consumerr.io/',
		'sender' => NULL,
		'exclude' => array(
			'ip' => array(),
			'error' => array(),
		),
		'cache' => array(
			'enable' => FALSE,
		),
	);

	/**
	 * @var \ConsumErr\Entities\Access
	 */
	private static $access;

	/**
	 * @var \ConsumErr\Sender\ISender
	 */
	private static $sender;


	/**
	 * @param array $options
	 */
	public static function init($options = array())
	{
		self::$options = $options + self::$options;

		self::getTime();

		if (isset($_SERVER['REQUEST_URI'])) {
			self::getAccess()->setUrl((isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https://' : 'http://')
				. (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : ''))
				. $_SERVER['REQUEST_URI']);
		} else {
			self::getAccess()->setUrl(empty($_SERVER['argv']) ? 'CLI' : 'CLI: ' . implode(' ', $_SERVER['argv']));
			self::getAccess()->setName('$ ' . basename($_SERVER['argv'][0]) . ' ' . implode(' ', array_slice($_SERVER['argv'], 1)));
			self::getAccess()->setBackgroundJob(TRUE);
		}

		error_reporting(E_ALL | E_STRICT);


		set_exception_handler(array(__CLASS__, 'exceptionHandler'));
		set_error_handler(array(__CLASS__, 'errorHandler'));
	}

    public static function registerShutdownHandler()
    {
        register_shutdown_function(array(__CLASS__, 'shutdownHandler'));
    }


	/**
	 * @return Entities\Access
	 */
	protected static function getAccess()
	{
		if (!self::$access) {
			self::$access = new Entities\Access;
		}
		return self::$access;
	}


	/**
	 * @return Sender\ISender
	 */
	protected static function getSender()
	{
		if (!self::$sender) {
            $senderClass = self::getSenderClass();

            self::$sender = new $senderClass(self::$options['id'], self::$options['secret'], self::$options['url']);
		}
		return self::$sender;
	}


	/**
	 * @param Sender\ISender $sender
	 */
	public static function setSender(Sender\ISender $sender)
	{
		self::$sender = $sender;
	}


	/**
	 * @return array
	 */
	public static function getOptions()
	{
		return self::$options;
	}


	/**
	 * @return float
	 */
	public static function getTime()
	{
		if (!($time = self::getAccess()->getTime())) {
			self::getAccess()->setTime($time = (isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(TRUE)));
		}
		return $time;
	}


	/**
	 * @param string $name
	 */
	public static function setName($name)
	{
		self::getAccess()->setName($name);
	}


	/**
	 * @param string $url
	 */
	public static function setUrl($url)
	{
		self::getAccess()->setUrl($url);
	}


	/**
	 * @param bool $backgroundJob
	 */
	public static function setBackgroundJob($backgroundJob = TRUE)
	{
		self::getAccess()->getBackgroundJob($backgroundJob);
	}


	/**
	 * @param \Exception $exception
	 * @return \ConsumErr\Entities\Error
	 */
	public static function addError(\Exception $exception)
	{
		if ($exception instanceof \ErrorException &&
			in_array($exception->getSeverity(), (array) self::$options['exclude']['error']))
		{
			return NULL;
		}

		$exception = new Entities\Error($exception);
		self::getAccess()->addError($exception);

		return $exception;
	}


	/**
	 * @param string $message
	 * @param integer $num
	 * @param string $file
	 * @param integer $line
	 * @return \ConsumErr\Entities\Error
	 */
	public static function addErrorMessage($message, $num = E_USER_ERROR, $file = '', $line = 0)
	{
		if (is_array($message)) {
			$message = implode(' ', $message);
		}
		return self::addError(new \ErrorException($message, 0, $num, $file, $line));
	}


	/**
	 * @param string $category
	 * @param string $action
	 * @param string $label
	 * @param string $value
	 */
	public static function addEvent($category, $action, $label = '', $value = '')
	{
		self::getAccess()->addEvent(new Entities\Event($category, $action, $label, $value));
	}


	/**
	 * @param string $name
	 */
	public static function addPart($name = '')
	{
		self::getAccess()->addPart(new Entities\Part(-1 * self::getTime() + microtime(TRUE), $name));
	}


	/**
	 *
	 */
	public static function shutdownHandler()
	{
		if (!is_null($e = error_get_last())) {
			self::errorHandler($e['type'], $e['message'], $e['file'], $e['line']);
		}

		self::getAccess()->setMemory(function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : NULL);
		self::getAccess()->setTime(-1 * self::getTime() + microtime(TRUE));

		if (!in_array(
				isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : php_uname('n'),
				(array) self::$options['exclude']['ip'],
				TRUE
		)) {
			self::getSender()->send(array((string)self::getAccess()));
		}
	}


	/**
	 * @param \Exception $e
	 */
	public static function exceptionHandler(\Exception $e)
	{
		self::addError($e);
	}


	/**
	 * @param integer $num
	 * @param string $str
	 * @param string $file
	 * @param integer $line
	 * @param string|null $context
	 */
	public static function errorHandler($num, $str, $file, $line, $context = NULL)
	{
		self::addErrorMessage($str, $num, $file, $line);
	}

    /**
     * @return string
     */
    public static function getSenderClass()
    {
        if (!self::$options['sender']) {
            if (function_exists('extension_loaded') && extension_loaded('curl')) {
                $senderClass = /**/'ConsumErr\Sender\CurlSender' /**/ /*5.2*'ConsumErr_CurlSender'*/;
            } else {
                $senderClass = /**/'ConsumErr\Sender\PhpSender' /**/ /*5.2*'ConsumErr_PhpSender'*/;
            }
        } else {
            $senderClass = self::$options['sender'];
        }

        return $senderClass;
    }

}
