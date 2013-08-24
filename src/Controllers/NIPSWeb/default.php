<?php
/**
 * Main renderer for NIPSWeb
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 11032013
 * @package MyURY_NIPSWeb
 */
require 'Views/bootstrap.php';

CoreUtils::requireTimeslot();

if (isset($_REQUEST['readonly'])) {
  $template = 'NIPSWeb/readonly.twig';
  $title = 'URY Preview';
  $reslists = [];
} else {
  $template = 'NIPSWeb/main.twig';
  $title = 'Show Planner';
  $reslists = CoreUtils::dataSourceParser(array(
            'managed' => iTones_Playlist::getAlliTonesPlaylists(),
            'auto' => NIPSWeb_AutoPlaylist::getAllAutoPlaylists(),
            'aux' => NIPSWeb_ManagedPlaylist::getAllManagedPlaylists(),
            'user' => NIPSWeb_ManagedUserPlaylist::getAllManagedUserPlaylistsFor(User::getInstance())
        ));
}

$twig->setTemplate($template)
        ->addVariable('title', $title)
        ->addVariable('tracks', MyURY_Timeslot::getInstance($_SESSION['timeslotid'])->getShowPlan())
        ->addVariable('reslists', $reslists)
        ->render();