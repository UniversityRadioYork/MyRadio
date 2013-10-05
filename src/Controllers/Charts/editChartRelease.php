<?php
/**
 * Allows the editing of chart releases.
 * @version 20130427
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyURY_Charts
 */

$types = MyURY_ChartType::getAll();
$type_select = [['text' => 'Please select...', 'disabled' => true]];
foreach ($types as $type) {
  $type_select[] = [
    'value' => $type->getID(),
    'text' => $type->getDescription()
  ];
}

$form = MyURY_JsonFormLoader::loadFromModule(
  $module, 'chartreleasefrm', 'doEditChartRelease',
  ['chart_types' => $type_select]
);

if ($_REQUEST['chart_release_id']) {
  $chart_release = MyURY_ChartRelease::getInstance($_REQUEST['chart_release_id']);

  // Temporary hack until tabular stuff appears.
  $chart_rows = $chart_release->getChartRows();
  $chart_rows_form = [];
  for ($i = 0; $i < 10; $i++) {
    $row = $chart_rows[$i]; 
    $chart_rows_form['track' . ($i + 1)] = $row->getTrack();
  }

  $form->editMode(
    $chart_release->getID(),
    array_merge(
      [
        'submitted_time' =>
          CoreUtils::happyTime($chart_release->getReleaseTime(), false),
        'chart_type_id' =>
          $chart_release->getChartTypeID()
      ],
      $chart_rows_form
    )
  );

} else {
  $form->setTitle('Create Chart Release');
  $form->setFieldValue('submitted_time', CoreUtils::happyTime(time(), false));
}

$form->render();
?>
