<?php

/**
 * Allows the addition of new quotes.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Quote;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Random Quote')
    ->addVariable('tabledata', CoreUtils::setToDataSource(MyRadio_Quote::getRandom()))
    ->addVariable('tablescript', 'myradio.quotes')
    ->render();
