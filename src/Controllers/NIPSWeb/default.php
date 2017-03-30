<?php
/**
 * Main renderer for NIPSWeb.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;
use \MyRadio\iTones\iTones_Playlist;
use \MyRadio\NIPSWeb\NIPSWeb_AutoPlaylist;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedPlaylist;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedUserPlaylist;
use \MyRadio\ServiceAPI\MyRadio_User;

CoreUtils::requireTimeslot();

    $show_title = MyRadio_Timeslot::getInstance($_SESSION['timeslotid'])->getMeta('title');
    $start_time = CoreUtils::HappyTime(MyRadio_Timeslot::getInstance($_SESSION['timeslotid'])->getStartTime());

if (isset($_REQUEST['readonly'])) {
    $template = 'NIPSWeb/readonly.twig';
    $reslists = [];
} else {
    $template = 'NIPSWeb/main.twig';
    $reslists = CoreUtils::dataSourceParser(
        [
            'managed' => iTones_Playlist::getAlliTonesPlaylists(),
            'auto' => NIPSWeb_AutoPlaylist::getAllAutoPlaylists(),
            'aux' => NIPSWeb_ManagedPlaylist::getAllManagedPlaylists(),
            'user' => NIPSWeb_ManagedUserPlaylist::getAllManagedUserPlaylistsFor(MyRadio_User::getInstance()),
        ]
    );
}

CoreUtils::getTemplateObject()->setTemplate($template)
    ->addVariable('title', "Show Planner")
    ->addVariable('show_title', $show_title)
    ->addVariable('start_time', $start_time)
    ->addVariable('tracks', MyRadio_Timeslot::getInstance($_SESSION['timeslotid'])->getShowPlan())
    ->addVariable('reslists', $reslists)
    ->render();
