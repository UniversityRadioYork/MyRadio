<?php

/**
 * Shows statistics about members actually turning up for their shows.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130829
 * @package MyRadio_Scheduler
 */
$data = [];

foreach (MyRadio_Season::getAllSeasonsInLatestTerm() as $season) {
  $info = $season->getAttendanceInfo();
  $data[] = [
      'title' => $season->getMeta('title'),
      'percent' => (int)$info[0],
      'missed' => (int)$info[1]
  ];
}

$twig = CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('title', 'Show Attendence')
        ->addVariable('tabledata', $data)
        ->addVariable('tablescript', 'myury.datatable.default');

$twig->render();
