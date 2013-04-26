<?php
/**
 * Scan music library, filling in blanks or changing default values
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 * @version 24042013
 * @package MyURY_Library
 */

$tracks = MyURY_Track::getAllDigitised();

$missing = array();

foreach ($tracks as $track) {
  if (!$track->checkForAudioFile()) {
    $missing[] = $track;
    if (isset($_GET['fix'])) {
      $track->setDigitised(false);
    }
  }
}


require 'Views/MyURY/Library/bootstrap.php';
$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.datatable.default')
        ->addVariable('title', 'Missing Track Files')
        ->addVariable('tabledata', CoreUtils::dataSourceParser($missing))
        ->addInfo('Please ensure the information below seems correct, then <a href="'.
                CoreUtils::makeURL('Library', 'findMissing', array('fix' => 1)).'">click here</a> to mark these files
                  as undigitised.',
                'wrench')
        ->render();