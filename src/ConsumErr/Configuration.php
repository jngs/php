<?php
namespace ConsumErr;


class Configuration
{

    private $senderAlias = array(
        'php' => /**/
            'ConsumErr\Sender\PhpSender' /**/ /*5.2*'ConsumErr_PhpSender'*/,
        'curl' => /**/
            'ConsumErr\Sender\CurlSender' /**/ /*5.2*'ConsumErr_CurlSender'*/,
    );

    private $severityAlias = array(
        'error' => array(E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_CORE_WARNING, E_COMPILE_WARNING),
        'warning' => array(E_WARNING, E_USER_WARNING),
        'notice' => array(E_NOTICE, E_USER_NOTICE, E_STRICT),
        'deprecated' => array(E_DEPRECATED, E_USER_DEPRECATED),
    );

    private $defaults = array(
        'token' => '',
        'url' => 'http://service.consumerr.io/',
        'sender' => NULL,
        'compress' => TRUE,
        'error_reporting' => NULL,
        'disabled' => array(
            'ip' => array(),
            'severity' => array(),
        ),
    );

    private $config;

    private $defaultErrorReporting;

    public function __construct($config = array())
    {
		$this->defaultErrorReporting = E_ALL & ~E_NOTICE;
        $this->config = $this->validateConfiguration($config);
    }


    /**
     * @param $left
     * @param $right
     * @return array
     *
     * @author David Grudl, Nette Framework
     */
    private function mergeConfiguration($left, $right)
    {
        if (is_array($left) && is_array($right)) {
            foreach ($left as $key => $val) {
                if (is_int($key)) {
                    $right[] = $val;
                } else {
                    if (isset($right[$key])) {
                        $val = $this->mergeConfiguration($val, $right[$key]);
                    }
                    $right[$key] = $val;
                }
            }

            return $right;

        } elseif ($left === NULL && is_array($right)) {
            return $right;

        } else {
            return $left;
        }
    }

    public function getErrorReportingLevel()
    {
        return isset($this->config['error_reporting']) ? $this->config['error_reporting'] : $this->defaultErrorReporting;
    }

    private function validateConfiguration($options)
    {
        $config = $this->mergeConfiguration($options, $this->defaults);
        if(empty($config['token']) && !empty($config['secret'])) {
            $config['token'] = $config['secret']; //BC
        }

        if (empty($config['token'])) {
            AssertionException::isEmpty('token');
        }

        $config['sender'] = $this->getSenderClass($options);

        $config['error_reporting'] = $this->processReportingConfig($config['error_reporting'], $this->defaultErrorReporting);

        $config['disabled']['severity'] = $this->processReportingConfig($config['disabled']['severity'], $config['error_reporting'] ^ ($this->defaultErrorReporting | E_NOTICE));

        if (!empty($config['disabled']['ip'])) {
            $list = $config['disabled']['ip'];
            $config['disabled']['ip'] = is_string($list)
                ? preg_split('#[,\s]+#', $list)
                : (array)$list;
        }

        if (isset($config['exclude'])) {
            trigger_error("Exclude config option is deprecated, use 'disabled' instead", E_USER_DEPRECATED);
        }
        if (isset($config['exclude']['error'])) {
            trigger_error("Exclude-error config option is deprecated, use 'disabled-severity' instead", E_USER_DEPRECATED);
        }

        return $config;

    }

    public function getSenderClass($options)
    {
        if (empty($options['sender'])) {
            if (function_exists('extension_loaded') && extension_loaded('curl') && PHP_VERSION_ID >= 50300) {
                return /**/
                    'ConsumErr\Sender\CurlSender' /**/ /*5.2*'ConsumErr_CurlSender'*/
                    ;
            } else {
                return /**/
                    'ConsumErr\Sender\PhpSender' /**/ /*5.2*'ConsumErr_PhpSender'*/
                    ;
            }
        } else {
            if (isset($this->senderAlias[$options['sender']])) {
                return $this->senderAlias[$options['sender']];
            } else {
                if (!class_exists($options['sender'])) {
                    throw new AssertionException("Sender class {$options['class']} was not found.");
                }

                return $options['sender'];
            }
        }
    }

    private function processReportingConfig($option, $default)
    {
        if (empty($option)) {
            return $default;
        } elseif (is_numeric($option)) {
            return $option;
        } elseif (is_array($option)) {
            return $this->convertErrorReportingAliases($option);

        }

        return $default;

    }


    public function getSenderInstance()
    {
        if (empty($this->config['sender'])) {
            throw new \RuntimeException("Invalid configuration - sender class is not set.");
        }

        $class = $this->config['sender'];

        return new $class($this);
    }

    public function isClientDisabled()
    {

        $addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : php_uname('n');

        return in_array($addr, $this->config['disabled']['ip'], TRUE);
    }

    private function convertErrorReportingAliases($option)
    {
        $result = 0;
        foreach ($option as $level) {
            if (isset($this->severityAlias[$level])) {
                $result = array_sum($this->severityAlias[$level]);
            } elseif (is_int($level)) {
                $result += $level;
            } else {
                throw new AssertionException("Invalid value '$level' for error reporting.");
            }
        }

        return $result;
    }

    public function getToken()
    {
        return $this->config['token'];
    }

    public function getApiEndpoint()
    {
        return $this->config['url'];
    }

    public function isErrorDisabled($severity)
    {
        return  (($severity & $this->config['disabled']['severity']) === $severity);
    }

    public function isCompressionEnabled()
    {
        return function_exists("gzcompress") && $this->config['compress'];
    }

    public function getLogFile()
    {
        return !empty($this->config['log']) ? $this->config['log'] : NULL;
    }


}