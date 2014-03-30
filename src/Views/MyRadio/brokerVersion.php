<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 15082012
 * @package MyRadio_Core
 */
$twig = CoreUtils::getTemplateObject();
$twig->addVariable('serviceName', 'MyRadio')
        ->setTemplate('MyRadio/brokerVersion.twig')
        ->addVariable('uri', $_SERVER['REQUEST_URI'])
        ->addVariable('title', 'Version Selector')
        ->addVariable('versions', $versions)
        ->render();
