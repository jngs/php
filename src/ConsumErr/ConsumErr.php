<?php

namespace ConsumErr;

use ConsumErr\Entities;

/**/
class_alias('ConsumErr\ConsumErr', 'ConsumErr'); /**/

if (!defined("E_DEPRECATED")) {
    define("E_DEPRECATED", 8192);
}
if (!defined("E_USER_DEPRECATED")) {
    define("E_USER_DEPRECATED", 16384);
}


class ConsumErr
{

    const EXTENSION_NAME = 'php';
    const VERSION = '1.1.0';
    const VERSION_CODE = 10100;

    /**
     * @var \ConsumErr\Entities\Access
     */
    private static $access;

    /**
     * @var \ConsumErr\Sender\ISender
     */
    private static $sender;

    /** @var bool */
    private static $enabled = FALSE;

    /** @var Configuration */
    private static $configuration;


    /**
     * @deprecated
     * @param array $options
     */
    public static function init($options = array())
    {
        self::enable($options);
    }

    /**
     * Enables Consumerr error handlers
     * @param array|Configuration $configuration You can set your own instance of Configuration or just config options
     */
    public static function enable($configuration = array())
    {
        if ($configuration instanceof Configuration) {
            self::$configuration = $configuration;
        } else {
            self::$configuration = new Configuration($configuration);
        }

        if (self::$configuration->isClientDisabled()) {
            return; //nothing to do here
        }

        self::initialize();

        error_reporting(self::$configuration->getErrorReportingLevel());

        set_exception_handler(array(__CLASS__, 'exceptionHandler'));
        set_error_handler(array(__CLASS__, 'errorHandler'));

        register_shutdown_function(array(__CLASS__, 'errorShutdownHandler'));
        self::registerSenderShutdownHandler();
    }

    /**
     * @internal
     */
    public static function initialize()
    {
        self::$enabled = TRUE;
        self::getTime();

        if (!self::isConsole()) {
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
    }

    public static function registerSenderShutdownHandler()
    {
        register_shutdown_function(array(__CLASS__, 'senderShutdownHandler'));
    }

    public static function ignoreAccess($ignore = TRUE)
    {
        self::$enabled = !$ignore;
    }

    public static function isEnabled()
    {
        return self::$enabled;
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
        self::getAccess()->getBackgroundJob($backgroundJob);
    }


    /**
     * @param \Exception $exception
     * @return \ConsumErr\Entities\Error
     */
    public static function addError(\Exception $exception)
    {
        if ($exception instanceof \ErrorException && self::$configuration->isErrorDisabled($exception->getSeverity())) {
            return NULL;
        }

        $exception = new Entities\Error($exception);
        self::getAccess()->addError($exception);

        return $exception;
    }


    /**
     * @param string $message
     * @param int $num
     * @param string $file
     * @param integer $line
     * @param array $context
     * @return \ConsumErr\Entities\Error
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
    public static function senderShutdownHandler()
    {
        if (!self::$enabled) {
            return;
        }
        self::getAccess()->setMemory(function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : NULL);
        self::getAccess()->setTime(-1 * self::getTime() + microtime(TRUE));

        list($accessData, $encoding) = self::encodeData((string) self::getAccess());

        self::getSender()->send($accessData, $encoding);
    }


    /**
     * @param \Exception $e
     */
    public static function exceptionHandler(\Exception $e)
    {
        self::addError($e);
    }


    public static function errorHandler($severity, $message, $file, $line, $context = NULL)
    {
        if (($severity & error_reporting()) !== $severity) {
            return FALSE;
        }

        self::addErrorMessage($message, $severity, $file, $line, $context);

        return NULL;
    }

    public static function addExtension($extensionName, $versionCode)
    {
        self::getAccess()->addExtensionVersion($extensionName, $versionCode);
    }

    /***/
    public static function addLibrary($name, $versionCode)
    {
        self::getAccess()->addLibraryVersion($name, $versionCode);
    }

    public static function errorShutdownHandler()
    {
        $error = error_get_last();
        if (in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_RECOVERABLE_ERROR, E_USER_ERROR))) {
            self::errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
        }
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
     * @param Configuration $configuration
     */
    public static function setConfiguration($configuration)
    {
        self::$configuration = $configuration;
    }

    private static function encodeData($param)
    {
        $encoding = 'plain';
        if(function_exists("gzcompress")) {
            $encoding = 'gzip';
            $param = gzcompress($param);
        }
        return array(base64_encode($param), $encoding);
    }

}
