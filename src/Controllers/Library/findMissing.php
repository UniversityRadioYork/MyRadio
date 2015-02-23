<?php
/**
 * Scan music library, filling in blanks or changing default values
 * @package MyRadio_Library
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;

$tracks = MyRadio_Track::getAllDigitised();

$missing = [];

foreach ($tracks as $track) {
    if (!$track->checkForAudioFile()) {
        $missing[] = $track;
        if (isset($_GET['fix'])) {
            $track->setDigitised(false);
        }
    }
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myury.datatable.default')
    ->addVariable('title', 'Missing Track Files')
    ->addVariable('tabledata', CoreUtils::dataSourceParser($missing))
    ->addInfo(
        'Please ensure the information below seems correct, then <a href="'
        .CoreUtils::makeURL('Library', 'findMissing', ['fix' => 1])
        .'">click here</a> to mark these files as undigitised.',
        'wrench'
    )->render();
