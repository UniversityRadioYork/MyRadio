<?php
/**
 * Scan music library, filling in blanks or changing default values
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 * @version 21042013
 * @package MyURY_Library
 */

$albums = MyURY_Album::findByName('URY Downloads', 10);

$cacher = APCProvider::getInstance();

$checked = $cacher->get('myuryLibraryGapFillerCheckedTracks') || array();

$limit = 50;
$updated = array();
foreach ($albums as $album) {
  $tracks = $album->getTracks();
  foreach ($tracks as $track) {
    if ($limit <= 0) break;
    if (in_array($track->getID(), $checked));
    $track->updateInfoFromLastfm();
    $updated[] = $track;
    $checked[] = $track->getID();
    usleep(200000);
    $limit--;
  }
}

$cacher->set('myuryLibraryGapFillerCheckedTracks', $checked);

require 'Views/MyURY/Library/bootstrap.php';
$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.library.gapfiller')
        ->addVariable('title', 'Updated Tracks')
        ->addVariable('tabledata', CoreUtils::dataSourceParser($updated))
        ->render();