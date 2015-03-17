<?php
/**
 * Scan music library, finding tracks that don't seem to be where they should be
 * @package MyRadio_Library
 */

use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;

$wrong = [];

foreach ($container['config']->music_central_db_exts as $ext) {
    foreach (glob($container['config']->music_central_db_path.'/records/*/*.'.$ext) as $file) {
        $recordid = preg_replace('/^.*\/([0-9]+)\/[0-9]+\.'.$ext.'$/', '$1', $file);
        $trackid = preg_replace('/^.*\/([0-9]+)\.'.$ext.'$/', '$1', $file);

        try {
            $track = MyRadio_Track::getInstance($trackid);
            if ($track->getAlbum()->getID() != $recordid) {
                $wrong[] = [$file, $track->getAlbum()->getID()];

                if (isset($_GET['fix'])) {
                    if (!is_dir($container['config']->music_central_db_path.'/records/'.$track->getAlbum()->getID())) {
                        mkdir($container['config']->music_central_db_path.'/records/'.$track->getAlbum()->getID());
                    }
                    if (copy($file, $track->getPath($ext))) {
                        unlink($file);
                    }
                }
            }

            $track->removeInstance();
            unset($track);
        } catch (MyRadioException $e) {
            $wrong[] = [$file, 0];
        }
    }
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myury.datatable.default')
    ->addVariable('title', 'Misplaced Tracks')
    ->addVariable('tabledata', CoreUtils::dataSourceParser($wrong))
    ->addInfo(
        'Please ensure the information below seems correct, then <a href="'
        .CoreUtils::makeURL('Library', 'findWrong', ['fix' => 1])
        .'">click here</a> to auto-move files that have a guessed correct location.',
        'wrench'
    )->render();
