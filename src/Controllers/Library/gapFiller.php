<?php
/**
 * Scan music library, filling in blanks or changing default values
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 * @version 21042013
 * @package MyRadio_Library
 */

$albums = MyRadio_Album::findByName(Config::$short_name, 10);

$cacher = APCProvider::getInstance();

$checked = $cacher->get('myradioLibraryGapFillerCheckedTracks');
if (!is_array($checked)) {
    $checked = [];
}

$limit = 150;
$updated = [];
foreach ($albums as $album) {
    $tracks = $album->getTracks();
    foreach ($tracks as $track) {
        if ($limit <= 0) {
            break;
        }
        if (in_array($track->getID(), $checked)) {
            continue;
        }
        $track->updateInfoFromLastfm();
        $updated[] = $track;
        $checked[] = $track->getID();
        usleep(200000);
        $limit--;
    }
}

$cacher->set('myradioLibraryGapFillerCheckedTracks', $checked);

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myury.library.gapfiller')
    ->addVariable('title', 'Updated Tracks')
    ->addVariable('tabledata', CoreUtils::dataSourceParser($updated))
    ->render();
