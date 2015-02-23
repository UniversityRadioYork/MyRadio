<?php
/**
 *
 * @todo Proper Documentation
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\CoreUtils;

header('HTTP/1.1 500 Internal Server Error');

CoreUtils::getTemplateObject()->setTemplate('error.twig')
    ->addVariable('serviceName', 'Error')
    ->addVariable('title', 'You Mean I\'m Not Really a Teapot?')
    ->addVariable(
        'body',
        '<p>I\'m sorry, but the server got very, very confused trying to do what you asked and had an existentical crisis.</p>
        <p>You could try again in a few minutes once it has calmed down and realised it is not a container for hot beverages.</p>'
    )
    ->render();
