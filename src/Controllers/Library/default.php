<?php

/*
 * Initial buggeration of the Library Management Page


 * @package MyRadio_Library
 */

use \MyRadio\MyRadio\CoreUtils;

CoreUtils::getTemplateObject()->setTemplate('MyRadio/text.twig')
    ->addVariable('title', 'Library')
    ->addVariable(
        'text',
        'This part of MyRadio allows you to do some library management.'
    )->render();
