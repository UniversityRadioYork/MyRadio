<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 15082012
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\CoreUtils;

CoreUtils::getTemplateObject()->setTemplate('MyRadio/brokerVersion.twig')
    ->addVariable('serviceName', 'MyRadio')
    ->addVariable('uri', $_SERVER['REQUEST_URI'])
    ->addVariable('title', 'Version Selector')
    ->addVariable('versions', $versions)
    ->render();
