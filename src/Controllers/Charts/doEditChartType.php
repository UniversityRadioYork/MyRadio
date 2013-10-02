<?php
/**
 * Performs the actual editing of chart types.
 * @version 20130426
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyURY_Charts
 */

$form = MyURY_JsonFormLoader::loadFromModule(
  $module, 'charttypefrm', 'doEditChartType'
);

$data = $form->editMode(null, null)->readValues();
$chart_type = MyURY_ChartType::getInstance($data['myuryfrmedid']);
$chart_type->setName($data['name'])->setDescription($data['description']);

require 'Views/MyURY/Core/back.php';
