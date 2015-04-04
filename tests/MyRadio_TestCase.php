<?php
use \MyRadio\MyRadio\CoreUtils;

class MyRadio_TestCase extends PHPUnit_Framework_TestCase {
	function wireMockDatabase($func)
	{
		$container = CoreUtils::getContainer();
		$container['database'] = $func;
		CoreUtils::registerContainer($container);
	}
}
