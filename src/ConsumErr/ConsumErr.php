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

    private static $options = array(
        'id' => '',
        'secret' => '',
        'url' => 'http://service.consumerr.io/',
        'sender' => NULL,
        'error_reporting' => NULL,
        'exclude' => array(
            'ip' => array(),
            'error' => array(),
        ),
        'cache' => array(
            'enable' => FALSE,
        ),
    );

    private static $senders = array(
        'php' => 'Consumerr\\Sender\\PhpSender',
        'curl' => 'Consumerr\\Sender\\CurlSender',
    );


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
     * @param array $options
     */
    public static function enable($options = array())
    {
        self::$options = $options + self::$options;

        self::initialize();

        if (empty($options['error_reporting'])) {
            $options['error_reporting'] = E_ALL | E_STRICT ^ E_NOTICE;
        }

        error_reporting($options['error_reporting']);

        set_exception_handler(array(__CLASS__, 'exceptionHandler'));
        set_error_handler(array(__CLASS__, 'errorHandler'));

        register_shutdown_function(array(__CLASS__, 'errorShutdownHandler'));
        self::registerSenderShutdownHandler();
    }

    public static function initialize()
    {
        self::$enabled = TRUE;
        self::getTime();

        if (isset($_SERVER['REQUEST_URI'])) {
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
            self::getAccess()->setUrl(empty($_SERVER['argv']) ? 'CLI' : 'CLI: ' . implode(' ', $_SERVER['argv']));
            self::getAccess()
                ->setName('$ ' . basename($_SERVER['argv'][0]) . ' ' . implode(' ', array_slice($_SERVER['argv'], 1)));
            self::getAccess()->setBackgroundJob(TRUE);
        }
    }

    public static function registerSenderShutdownHandler()
    {
        register_shutdown_function(array(__CLASS__, 'senderShutdownHandler'));
    }

    public static function setOptions($options)
    {
        self::$options = array_merge(self::$options, $options);
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
        if ($exception instanceof \ErrorException &&
            in_array($exception->getSeverity(), (array)self::$options['exclude']['error'])
        ) {
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

        if (!in_array(
            isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : php_uname('n'),
            (array)self::$options['exclude']['ip'],
            TRUE
        )
        ) {
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
        self::addErrorMessage($str, $num, $file, $line, $context);
    }

    /**
     * @return string
     */
    public static function getSenderClass()
    {
        if (!self::$options['sender']) {
            if (function_exists('extension_loaded') && extension_loaded('curl')) {
                $senderClass = /**/
                    'ConsumErr\Sender\CurlSender' /**/ /*5.2*'ConsumErr_CurlSender'*/
                ;
            } else {
                $senderClass = /**/
                    'ConsumErr\Sender\PhpSender' /**/ /*5.2*'ConsumErr_PhpSender'*/
                ;
            }
        } else {
            if (isset(self::$senders[self::$options['sender']])) {
                self::$options['sender'] = self::$senders[self::$options['sender']];
            }
            $senderClass = self::$options['sender'];
        }

        return $senderClass;
    }

    public static function addExtension($extensionName, $versionCode)
    {
        self::getAccess()->addExtensionVersion($extensionName, $versionCode);
    }

    public static function errorShutdownHandler()
    {
        $error = error_get_last();
        if (in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE))) {
            self::errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

}
