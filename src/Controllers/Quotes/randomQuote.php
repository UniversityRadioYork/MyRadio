<?php

/**
 * Allows the viewing of a single random quote
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Quote;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Random Quote')
    ->addVariable('tabledata', CoreUtils::setToDataSource(MyRadio_Quote::getRandom()))
    ->addVariable('tablescript', 'myradio.quotes')
    ->render();
