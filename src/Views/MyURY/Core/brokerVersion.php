<?php

/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 15082012
 * @package MyURY_Core
 */
$twig = CoreUtils::getTemplateObject();
$twig->addVariable('serviceName', $service)
        ->addVariable('serviceVersion', $service_version)
        ->setTemplate('stripe.twig')
        ->addVariable('title', $module)
        ->addVariable('uri', $_SERVER['REQUEST_URI'])
        ->addVariable('title', 'Version Selector')
        ->addVariable('heading', 'Version Selector')
        ->addVariable('versions', $versions)
        ->render();