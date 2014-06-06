<?php
/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130809
 * @package MyRadio_Core
 */
header('HTTP/1.1 404 File Not Found');

CoreUtils::getTemplateObject()->setTemplate('error.twig')
    ->addVariable('serviceName', 'Error')
    ->addVariable('title', '404 - Page not found')
    ->addVariable(
        'body',
        '<p>That page doesn\'t seem to exist. Sorry about that :/</p>
    )
    ->render();
