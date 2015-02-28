<?php
/**
 *
 * @todo Proper Documentation
 * @package MyRadio_Core
 */

use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;

header('HTTP/1.1 404 File Not Found');

CoreUtils::getTemplateObject()->setTemplate('error.twig')
    ->addVariable('serviceName', 'Error')
    ->addVariable('title', '404 - Page not found')
    ->addVariable(
        'body',
        '<p>That page doesn\'t seem to exist. Sorry about that :\</p>'
    )
    ->render();
