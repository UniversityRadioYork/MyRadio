<?php

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Quotes')
    ->addVariable('tabledata', ServiceAPI::setToDataSource(MyRadio_Quote::getAll()))
    ->addVariable('tablescript', 'myradio.quotes')
    ->render();
