<?php
/**
 * Ajax handler for Timelord
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130905
 * @package MyURY_Timelord
 */

$sel = new MyURY_Selector();
$data = [
  'selector' => $sel->query(),
  'shows' => MyURY_Timeslot::getCurrentAndNext(null, 2),
  'breaking' => MyURYNews::getNewsItem(3),
  'ob' => $sel->remoteStreams(),
  'silence' => $sel->isSilence()
];

require 'Views/MyURY/datatojson.php';