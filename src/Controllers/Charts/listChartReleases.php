<?php
/**
 * Controller for listing all releases made for a given chart type.
 * @version 20131002
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Charts
 */

CoreUtils::getTemplateObject(
)->setTemplate(
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
?>
