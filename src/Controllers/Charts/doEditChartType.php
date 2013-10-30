<?php
/**
 * Performs the actual editing of chart types.
 * @version 20131002
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Charts
 */

$form = MyRadio_JsonFormLoader::loadFromModule(
  $module, 'charttypefrm', 'doEditChartType'
);

$data = $form->editMode(null, null)->readValues();
$chart_type = MyRadio_ChartType::getInstance($data['myradiofrmedid']);
$chart_type->setName($data['name'])->setDescription($data['description']);

require 'Views/MyRadio/back.php';
