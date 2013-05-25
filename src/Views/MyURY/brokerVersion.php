<?php

/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 15082012
 * @package MyURY_Core
 */
$twig = CoreUtils::getTemplateObject();
$twig->addVariable('serviceName', 'MyURY')
        ->setTemplate('MyURY/brokerVersion.twig')
        ->addVariable('uri', $_SERVER['REQUEST_URI'])
        ->addVariable('title', 'Version Selector')
        ->addVariable('heading', 'Version Selector')
        ->addVariable('versions', $versions)
        ->render();