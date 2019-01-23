<?php

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_ChartType;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Charts')
    ->addVariable('tablescript', 'myradio.charts')
    ->addVariable('tabledata', CoreUtils::setToDataSource(MyRadio_ChartType::getAll()))
    ->render();
