<?php
/**
 * Controller for listing all releases made for a given chart type.
 * @package MyRadio_Charts
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\ServiceAPI;
use \MyRadio\ServiceAPI\MyRadio_ChartType;

CoreUtils::getTemplateObject()->setTemplate(
    'table.twig'
)->addVariable(
    'tablescript',
    'myury.datatable.default'
)->addVariable(
    'title',
    'Chart Releases'
)->addVariable(
    'tabledata',
    ServiceAPI::setToDataSource(
        MyRadio_ChartType::getInstance(
            $_REQUEST['chart_type_id']
        )->getReleases()
    )
)->render();
