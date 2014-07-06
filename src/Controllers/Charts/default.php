<?php

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Charts')
    ->addVariable('tablescript', 'myradio.charts')
    ->addVariable('tabledata', ServiceAPI::setToDataSource(MyRadio_ChartType::getAll()))
    ->render();
