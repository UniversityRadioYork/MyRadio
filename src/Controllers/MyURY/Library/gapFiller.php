<?php
/**
 * Scan music library, filling in blanks or changing default values
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 * @version 21042013
 * @package MyURY_Library
 */

$albums = MyURY_Album::findByName('URY Downloads', 10);

$limit = 50;
$updated = array();
foreach ($albums as $album) {
  $tracks = $album->getTracks();
  foreach ($tracks as $track) {
    if ($limit <= 0) break;
    $track->updateInfoFromLastfm();
    $updated[] = $track;
    usleep(200000);
    $limit--;
  }
}

require 'Views/MyURY/Library/bootstrap.php';
$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.library.gapfiller')
        ->addVariable('title', 'Updated Tracks')
        ->addVariable('tabledata', CoreUtils::dataSourceParser($updated))
        ->render();