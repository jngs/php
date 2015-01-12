<?php


namespace Consumerr;

/**
 * Class ErrorHandler
 * @package Consumerr
 *
 * @internal
 */
class ErrorHandler
{

	public static function register()
	{
		$self = new self();

		set_exception_handler(array($self, 'exceptionHandler'));
		set_error_handler(array($self, 'errorHandler'));

		register_shutdown_function(array($self, 'fatalErrorHandler'));

		return $self;
	}


	/**
	 * @param \Exception $e
	 */
	public function exceptionHandler(\Exception $e)
	{
		Consumerr::addError($e);
	}


	public function errorHandler($severity, $message, $file, $line, $context = NULL)
	{
		if (($severity & error_reporting()) !== $severity) {
			Consumerr::log("Error handler - severity $severity is ignored in current error_reporting setting.");

			return FALSE;
		}

		Consumerr::addErrorMessage($message, $severity, $file, $line, $context);

		return NULL;
	}

	public function fatalErrorHandler()
	{
		$error = error_get_last();

		if (in_array($error['type'], array(
			//only these errors cause immediate shutdown
			E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_RECOVERABLE_ERROR, E_USER_ERROR
		))) {
			self::errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
		}
	}

}