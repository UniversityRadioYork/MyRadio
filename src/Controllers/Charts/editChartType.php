<?php
/**
 * Allows the editing of chart types.
 * @version 20140113
 * @author  Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Charts
 */

$form = MyRadio_JsonFormLoader::loadFromModule(
  $module, 'editChartType', 'doEditChartType'
);

$chart_type = MyRadio_ChartType::getInstance($_REQUEST['chart_type_id']);

$form->editMode(
  $chart_type->getID(),
  [
    'name' => $chart_type->getName(),
    'description' => $chart_type->getDescription()
  ]
)->render();
