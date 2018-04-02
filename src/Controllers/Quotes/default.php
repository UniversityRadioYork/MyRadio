<?php

/**
 * Allows the addition of new quotes.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Quote;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Quotes')
    ->addVariable('tabledata', CoreUtils::setToDataSource(MyRadio_Quote::getAll()))
    ->addVariable('tablescript', 'myradio.quotes')
    ->render();
