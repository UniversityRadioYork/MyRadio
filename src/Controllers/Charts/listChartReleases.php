<?php
/**
 * Controller for listing all releases made for a given chart type.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_ChartType;

CoreUtils::getTemplateObject()->setTemplate(
    'table.twig'
)->addVariable(
    'tablescript',
    'myradio.datatable.default'
)->addVariable(
    'title',
    'Chart Releases'
)->addVariable(
    'tabledata',
    CoreUtils::setToDataSource(
        MyRadio_ChartType::getInstance(
            $_REQUEST['chart_type_id']
        )->getReleases()
    )
)->render();
