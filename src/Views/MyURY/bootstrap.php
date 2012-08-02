<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 */
$twig = CoreUtils::getTemplateObject();
$twig->addVariable('serviceName', $service)
     ->addVariable('serviceVersion', $service_version)
     ->addVariable('submenu', (new MyURYMenu())->getSubMenuForUser(CoreUtils::getModuleID(1, $module), $member))
     ->setTemplate('stripe.twig')
     ->addVariable('title', $module)
     ->addVariable('uri', $_SERVER['REQUEST_URI']);