<?php
/**
 * Presents a form to the user to enable them to move an Episode.
 */
use \MyRadio\Config;
use MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Season;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    // @todo this is a bit of a hack
    $season = MyRadio_Season::getInstance($_REQUEST['sched_add_episode-show_season_id']);
    //Get data
    $data = $season->getAddEpisodeForm()->readValues();

    if ($data['new_start_time'] === $data['new_end_time']) {
        $message = 'You can\'t have an episode start and end at the same time.';
        URLUtils::backWithMessage($message);
    } else {
        // Move
        $result = $season->addEpisode(
            $data['new_start_time'],
            $data['new_end_time']
        );

        if ($result) {
            $message = 'New episode created.';
        } else {
            $message = 'Something didn\'t work! Please ping Computing.';
        }

        URLUtils::backWithMessage($message);
    }
} else {
    //Not Submitted

    if (!isset($_REQUEST['show_season_id'])) {
        throw new MyRadioException('No seasonid provided.', 400);
    }

    $season = MyRadio_Season::getInstance($_REQUEST['show_season_id']);

    $season->getAddEpisodeForm()->render();
}
