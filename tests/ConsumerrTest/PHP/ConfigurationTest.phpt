<?php
/** @testCase */

namespace ConsumerrTest\PHP;


use ConsumErr\Configuration;
use ConsumErr\ConsumErr;
use ConsumErr\Logger;
use ConsumErr\NetteConsumErr;
use ConsumErr\Sender\CurlSender;
use ConsumErr\Sender\PhpSender;
use Nette\Application\Application;
use Nette\Diagnostics\Debugger;
use Tester\Assert;

require_once __DIR__ .'/../bootstrap.php';

class ConfigurationTest extends TestCase {


    public function testBasic()
	{

		$conf = new Configuration(array('token' => 'token123'));

		Assert::same('token123', $conf->getToken());
		Assert::true($conf->isErrorDisabled(E_NOTICE));
		Assert::same(E_ALL & ~E_NOTICE, $conf->getErrorReportingLevel());
		//test bc

		$conf = new Configuration(array('secret' => '123', 'id' => '345'));
		Assert::same('123', $conf->getToken());

		$conf = new Configuration(array(
				'token' => '123',
				'error_reporting' => E_PARSE,
				'log' => 'log.log',
				'sender' => 'php',
			));

		Assert::same(E_PARSE, $conf->getErrorReportingLevel());
		Assert::false($conf->isErrorDisabled(E_PARSE));
		Assert::true($conf->isErrorDisabled(E_NOTICE));
		Assert::same('log.log', $conf->getLogFile());
		Assert::same('ConsumErr\\Sender\\PhpSender',get_class($conf->getSenderInstance()));

		$conf = new Configuration(array(
				'token' => '123',
				'error_reporting' => E_ALL,
			));

		Assert::same(E_ALL, $conf->getErrorReportingLevel());
		Assert::false($conf->isErrorDisabled(E_NOTICE));
		Assert::false($conf->isErrorDisabled(E_CORE_ERROR));


	}

	public function testFail()
	{
		Assert::exception(function() {
				$c = new Configuration();
			}, 'ConsumErr\\AssertionException');

		Assert::error(function() {
				new Configuration(array(
						'token' => '123',
						'exclude' => array('ip' => array()),
					));
			}, E_USER_DEPRECATED);
	}

}

\run(new ConfigurationTest());