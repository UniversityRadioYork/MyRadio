<?php
namespace MyRadio\MyRadio;

use MyRadio\Iface\ServiceFactory;

class MyRadioServiceFactory implements ServiceFactory
{
	private $container;

	public function __construct($container)
	{
		$this->container = $container;
	}

    public function getInstanceOf($class, $id = null)
    {
    	if (!class_exists($class)) {
    		$class = '\\MyRadio\\ServiceAPI\\' . $class;
    	}
        return $class::getInstance($id, $this->container);
    }
}
