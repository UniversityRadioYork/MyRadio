<?php
/**
 * Scan music library, finding tracks that don't seem to be where they should be.
 */
use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;

$wrong = [];

foreach (Config::$music_central_db_exts as $ext) {
    foreach (glob(Config::$music_central_db_path.'/records/*/*.'.$ext) as $file) {
        $recordid = preg_replace('/^.*\/([0-9]+)\/[0-9]+\.'.$ext.'$/', '$1', $file);
        $trackid = preg_replace('/^.*\/([0-9]+)\.'.$ext.'$/', '$1', $file);

        try {
            $track = MyRadio_Track::getInstance($trackid);
            if ($track->getAlbum()->getID() != $recordid) {
                $wrong[] = [$file, $track->getAlbum()->getID()];

                if (isset($_GET['fix'])) {
                    if (!is_dir(Config::$music_central_db_path.'/records/'.$track->getAlbum()->getID())) {
                        mkdir(Config::$music_central_db_path.'/records/'.$track->getAlbum()->getID());
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
        .URLUtils::makeURL('Library', 'findWrong', ['fix' => 1])
        .'">click here</a> to auto-move files that have a guessed correct location.',
        'wrench'
    )->render();
