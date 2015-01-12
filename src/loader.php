<?php

require_once __DIR__ . '/Consumerr/exceptions.php';

function consumerr_autoload($className)
{
	$className = ltrim($className, '\\');
	$fileName  = '';

	if ($lastNsPos = strrpos($className, '\\')) {
		$namespace = substr($className, 0, $lastNsPos);
		$className = substr($className, $lastNsPos + 1);
		$fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
	}
	$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

	require __DIR__.'/'.$fileName;
}
spl_autoload_register('consumerr_autoload');
