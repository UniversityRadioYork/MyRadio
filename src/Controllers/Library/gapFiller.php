<?php
/**
 * Scan music library, filling in blanks or changing default values.
 */
use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Album;

$albums = MyRadio_Album::findByName(Config::$short_name, 10);

$cache = Config::$cache_provider;
$cacher = $cache::getInstance();

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
        --$limit;
    }
}

$cacher->set('myradioLibraryGapFillerCheckedTracks', $checked);

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.library.gapfiller')
    ->addVariable('title', 'Updated Tracks')
    ->addVariable('tabledata', CoreUtils::dataSourceParser($updated))
    ->render();
