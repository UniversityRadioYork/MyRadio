<?php
/**
 * @todo Proper Documentation
 */

use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;

header('HTTP/1.1 503 Service Unavailable');

$tmpl = CoreUtils::getTemplateObject()->setTemplate('error.twig')
    ->addVariable('serviceName', 'Error')
    ->addVariable('title', 'Down For Maintenance');

if (Config::$maintenance_modules === '*') {
    $tmpl = $tmpl->addVariable(
        'body',
        '<p>We\'re sorry, MyRadio is currently down because Computing are doing maintenance.</p>'
        . '<p>Please try again in a few hours or contact the Computing Team.</p>'
    );
} else {
    $tmpl = $tmpl->addVariable(
        'body',
        '<p>We\'re sorry, that MyRadio feature is currently disabled because Computing are doing maintenance.</p>'
        . '<p>Please try again in a few hours or contact the Computing Team.</p>'
    );
}

$tmpl->render();
