<?php
/**
 * Performs the actual editing of chart releases.
 * @version 20130426
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyURY_Charts
 */

$form = MyURY_JsonFormLoader::loadFromModule(
  $module, 'chartreleasefrm', 'doEditChartRelease',
  ['chart_types' => []]
);

$data = $form->editMode(null, null)->readValues();

if ($data['myuryfrmedid'] === '') {
  // Create a new chart release
  MyURY_ChartRelease::create($data);
  $chart_release_id = MyURY_ChartRelease::findReleaseIDOn(
    $data['submitted_time'],
    $data['chart_type_id']
  );

  for ($i = 1; $i <= 10; $i++) {
    MyURY_ChartRow::create(
      [
        'chart_release_id' => $chart_release_id,
        'position' => $i,
        'trackid' => $data['track' . $i]
      ]
    );
  }
} else {
  // Edit an existing one

  $chart_release = MyURY_ChartRelease::getInstance($data['myuryfrmedid']);

  $chart_release->setChartTypeID($data['chart_type_id'])->setReleaseTime($data['submitted_time']);

  foreach($chart_release->getChartRows() as $chart_row) {
    $chart_row->setTrackID($data['track' . $chart_row->getPosition()]);
  }
}

require 'Views/MyURY/Core/back.php';
