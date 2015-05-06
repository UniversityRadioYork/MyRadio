<?php
require_once __DIR__ . '/../src/Classes/MyRadioInit.php';
require_once __DIR__ . '/../src/Interfaces/SessionProvider.php';
require_once __DIR__ . '/../src/Classes/MyRadio/MyRadioNullSession.php';

use \Aura\Di\Container;
use \Aura\Di\Factory;
use \MyRadio\MyRadio\MyRadioNullSession;

class MyRadioTestInit extends \MyRadio\MyRadioInit {

	protected static function setupServiceContainer()
	{
		$container = parent::setupServiceContainer();

		$container->set('config', $container->lazy(function() {
			$c = new \MyRadio\Config;
			$c->display_errors = true;
			return $c;
		}));
		
		return $container;
	}

	public static function init()
	{
		self::setupPreConfigEnvironment();
        self::setupAutoLoaders();
        $container = self::setupServiceContainer();
        self::loadConfigDatabaseAndCheckForSetup($container);
        return $container;
	}
}

class DummySession extends MyRadioNullSession {
	private $d = [
		'auth_use_locked' => false
	];

    public function offsetSet($offset, $value)
    {
        return $this->d[$offset] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->d[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->d[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->d[$offset];
    }
}

MyRadioTestInit::init();
