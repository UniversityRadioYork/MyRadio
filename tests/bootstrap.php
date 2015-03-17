<?php
require_once __DIR__ . '/../src/Classes/MyRadioInit.php';
require_once __DIR__ . '/../src/Classes/ContainerSubject.php';

use \Pimple\Container;
class MyRadioTestInit extends \MyRadio\MyRadioInit {

	protected static function setupServiceContainer()
	{
		$container = new Container();
		$container['config'] = new \MyRadio\Config;
		return $container;
	}

	public static function init()
	{
		self::setupPreConfigEnvironment();
        self::setupAutoLoaders();
        $container = self::setupServiceContainer();
        \MyRadio\ContainerSubject::registerContainer($container);
        self::loadConfigAndCheckForSetup($container);
        return $container;
	}
}

MyRadioTestInit::init();
