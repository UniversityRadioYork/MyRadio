<?php

/*
 * Initial buggeration of the Library Management Page
 * @author Anthony Williams <anthony@ury.org.uk>
 * @version 25072012
 * @package MyRadio_Library
 */
CoreUtils::getTemplateObject()->setTemplate('MyRadio/text.twig')
    ->addVariable('title', 'Library')
    ->addVariable(
        'text',
        'This part of MyRadio allows you to do some library management.'
    )->render();
