<?php
/**
 *
 * @todo Proper Documentation
 * @package MyRadio_Scheduler
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Season;

if (!isset($_REQUEST['from']) or !isset($_REQUEST['into'])) {
	throw new MyRadioException('Two input Seasons (from and into) are required.', 400);
}

$duplicate_season = MyRadio_Season::getInstance($_REQUEST['from']);
$origin_season = MyRadio_Season::getInstance($_REQUEST['into']);

// Prerequisite checks
if ($duplicate_season->getShow() != $origin_season->getShow()) {
	throw new MyRadioException('Only Seasons in the same show can be merged.', 400);
}
if ($duplicate_season->getTerm() !== $origin_season->getTerm()) {
	throw new MyRadioException('Only Seasons in the same term can be merged.', 400);
}
if ($duplicate_season == $origin_season) {
	throw new MyRadioException('A Season cannot be merged with itself.', 400);
}

$origin_season->resolveMerge($duplicate_season);
