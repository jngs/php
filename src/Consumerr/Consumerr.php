<?php

namespace Consumerr;

use Consumerr\Entities;
use Consumerr\Sender\MessageFactory;

/**/
class_alias('Consumerr\Consumerr', 'Consumerr'); /**/

/*5.2*
if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}
*/

if (!defined("E_DEPRECATED")) {
	define("E_DEPRECATED", 8192);
}
if (!defined("E_USER_DEPRECATED")) {
	define("E_USER_DEPRECATED", 16384);
}


class Consumerr
{

	const EXTENSION_NAME = 'php';
	const VERSION = '1.3.0';
	const VERSION_CODE = 10300;

	/**
	 * @var \Consumerr\Entities\Access
	 */
	private static $access;

	/**
	 * @var \Consumerr\Sender\ISender
	 */
	private static $sender;

	/** @var bool */
	private static $enabled = FALSE;

	/** @var Configuration */
	private static $configuration;

	/** @var DebugLogger */
	private static $logger;


	/**
	 * @deprecated
	 * @param array $options
	 */
	public static function init($options = array())
	{
		trigger_error(__CLASS__.'::'.__METHOD__.' is deprecated. Use '.__CLASS__.'::enable instead.', E_USER_DEPRECATED);
		self::enable($options);
	}

	/**
	 * Enables Consumerr error handlers
	 *
	 * @param array|Configuration $configuration You can set your own instance of Configuration or just config options
	 */
	public static function enable($configuration = array())
	{
		self::setConfiguration($configuration);

		if (!self::initialize()) {
			return;
		}

		error_reporting(self::$configuration->getErrorReportingLevel());
	}

	/**
	 * Initialize Consumerr WITHOUT enabling its error handlers. Usefull when error handling is done elsewhere.
	 *
	 * @param Configuration|string|array $configuration optionally set configuration @see setConfiguration
	 * @return bool
	 */
	public static function initialize($configuration = NULL)
	{
		if($configuration) {
			self::setConfiguration($configuration);
		}

		if(!self::$configuration instanceof Configuration) {
			throw new InvalidConfigurationException("Consumerr is not properly configured.");
		}
		if(self::$enabled) {
			self::log("Multiple calls to initialize - ignore");
			return FALSE;
		}

		if (self::$configuration->isClientDisabled()) {
			self::log('Client IP is disabled by current configuration.');

			return FALSE; //nothing to do here
		}
		self::$enabled = TRUE;
		self::getTime();

		if (!self::isConsole()) {
			if (self::$configuration->isAsyncEnabled()) {
				ob_start();
			}

			self::getAccess()->setUrl(
				(isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https://' : 'http://')
				. (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : ''))
				. $_SERVER['REQUEST_URI']
			);

			if (isset($_SERVER['SERVER_NAME'])) {
				self::getAccess()->setServerName($_SERVER['SERVER_NAME']);
			}

			if (isset($_SERVER['REMOTE_ADDR'])) {
				self::getAccess()->setRemoteAddr($_SERVER['REMOTE_ADDR']);
			}

			if (isset($_SERVER['SCRIPT_NAME'])) {
				self::getAccess()->setName($_SERVER['SCRIPT_NAME']);
			}


			if (isset($_SERVER['HTTP_USER_AGENT'])) {
				self::getAccess()->setUserAgent($_SERVER['HTTP_USER_AGENT']);
			}
		} else {
			self::getAccess()->setName(self::getCliArguments());
			self::getAccess()->setBackgroundJob(TRUE);
		}

		self::registerSenderShutdownHandler();

		return TRUE;
	}

	/**
	 * @internal
	 */
	public static function registerSenderShutdownHandler()
	{
		register_shutdown_function(array(__CLASS__, 'senderShutdownHandler'));
		self::log("Sender shutdown handler registered");
	}

	/**
	 * Set or unset current request as ignored.
	 * @param bool $ignore TRUE - ignore, FALSE - don't ignore
	 */
	public static function ignoreAccess($ignore = TRUE)
	{
		self::$enabled = !$ignore;
		if ($ignore) {
			self::log("Consumerr disabled by ignoreAccess call. Access will by ignored.");
		}
	}

	/**
	 * Is Consumerr enabled?
	 *
	 * @return bool
	 */
	public static function isEnabled()
	{
		return self::$enabled;
	}


	/**
	 * @internal
	 * @return Entities\Access
	 */
	public static function getAccess()
	{
		if (!self::$access) {
			self::$access = new Entities\Access;
		}

		return self::$access;
	}


	/**
	 * @deprecated
	 * @return Sender\ISender
	 */
	protected static function getSender()
	{
		if (!self::$sender) {
			self::$sender = self::$configuration->getSenderInstance();
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
	 * @return float
	 */
	public static function getTime()
	{
		if (!($time = self::getAccess()->getTime())) {
			self::getAccess()
				->setTime($time = (isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(TRUE)));
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
		self::getAccess()->setBackgroundJob($backgroundJob);
		self::log("Current access set to background job set.");
	}


	/**
	 * Log exception
	 *
	 * @param \Exception $exception
	 * @return \Consumerr\Entities\Error
	 */
	public static function addError(\Exception $exception)
	{
		if ($exception instanceof \ErrorException && self::$configuration->isErrorDisabled($exception->getSeverity())) {
			self::log("Error handler - severity {$exception->getSeverity()} is ignored by current disabled-severity setting.");

			return NULL;
		}

		$error = new Entities\Error($exception);
		self::getAccess()->addError($error);
		self::log("Added error - '" . get_class($exception) . " - " . $exception->getMessage() . "'");

		return $error;
	}


	/**
	 * Adds PHP error
	 *
	 * @param string $message
	 * @param int $num
	 * @param string $file
	 * @param integer $line
	 * @param array $context
	 * @return \Consumerr\Entities\Error
	 */
	public static function addErrorMessage($message, $num = E_USER_ERROR, $file = '', $line = 0, $context = array())
	{
		if (is_array($message)) {
			$message = implode(' ', $message);
		}
		$exception = new \ErrorException($message, 0, $num, $file, $line);
		$exception->context = $context;

		return self::addError($exception);
	}


	/**
	 * Log event
	 *
	 * @param string $category
	 * @param string $action
	 * @param string $label
	 * @param string $value
	 */
	public static function addEvent($category, $action, $label = '', $value = '')
	{
		$event = new Entities\Event($category, $action, $label, $value);
		self::getAccess()->addEvent($event);
		self::log("Added event '" . json_encode($event->__toArray()) . "'");
	}


	/**
	 * @param string $name
	 */
	public static function addPart($name = '')
	{
		$part = new Entities\Part(-1 * self::getTime() + microtime(TRUE), $name);
		self::getAccess()->addPart($part);
		self::log("Added part '" . json_encode($part->__toArray()) . "'");
	}


	/**
	 *
	 */
	public static function closeHttpConnection() {
		header("Connection: close");
		header("Content-Encoding: none");
		$size = ob_get_length();
		header("Content-Length: $size");
		ob_end_flush();
		flush();
	}


	/**
	 * @internal
	 */
	public static function senderShutdownHandler()
	{
		if (!self::isConsole() && self::$configuration->isAsyncEnabled()) {
			self::closeHttpConnection(); // save request time by closing HTTP connection
		}

		if (!self::$enabled) {
			self::log("Shutdown - consumerr is disabled, nothing will be sent.");

			return;
		}
		$messageFactory = new MessageFactory(self::getAccess());
		register_shutdown_function(array($messageFactory, 'send')); //ensure sender is called last
	}


	public static function addExtension($extensionName, $versionCode)
	{
		self::getAccess()->addExtensionVersion($extensionName, $versionCode);
		self::log("Registered extension  $extensionName");
	}

	/***/
	public static function addLibrary($name, $versionCode)
	{
		self::getAccess()->addLibraryVersion($name, $versionCode);
	}

	/**
	 * @return bool
	 *
	 */
	public static function isConsole()
	{
		return (defined("PHP_SAPI") && PHP_SAPI === 'cli') || !isset($_SERVER['REQUEST_URI']);
	}

	/**
	 *
	 * @internal
	 * @return string
	 */
	public static function getCliArguments()
	{
		if (empty($_SERVER['argv'])) {
			return '';
		}

		return '$ ' . implode(' ', $_SERVER['argv']);
	}

	/**
	 * @return Configuration
	 */
	public static function getConfiguration()
	{
		return self::$configuration;
	}

	/**
	 * @param Configuration|array|string $configuration
	 */
	public static function setConfiguration($configuration)
	{
		if (is_string($configuration)) {
			$configuration = array('token' => $configuration);
		}
		if (!$configuration instanceof Configuration) {
			$configuration = new Configuration($configuration);
		}

		self::$configuration = $configuration;
		if (self::$configuration->getLogFile()) {
			self::$logger = new DebugLogger(self::$configuration->getLogFile());
		} else {
			self::$logger = NULL;
		}
		self::log("Configuration set - token '" . self::$configuration->getToken() . "'");
	}

	public static function log($string)
	{
		if (self::$logger) {
			self::$logger->log($string);
		}
	}

}
