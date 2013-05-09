<?php
/**
 * Main renderer for NIPSWeb
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 11032013
 * @package MyURY_NIPSWeb
 */
require 'Views/MyURY/bootstrap.php';

CoreUtils::requireTimeslot();

$twig->setTemplate('NIPSWeb/main.twig')
        ->addVariable('title', 'Show Planner')
        ->addVariable('heading', 'Show Planner')
        ->addVariable('tracks', MyURY_Timeslot::getInstance($_SESSION['timeslotid'])->getShowPlan())
        ->addVariable('reslists', CoreUtils::dataSourceParser(array(
            'managed' => iTones_Playlist::getAlliTonesPlaylists(),
            'auto' => NIPSWeb_AutoPlaylist::getAllAutoPlaylists(),
            'aux' => NIPSWeb_ManagedPlaylist::getAllManagedPlaylists(),
            'user' => NIPSWeb_ManagedUserPlaylist::getAllManagedUserPlaylistsFor(User::getInstance())
        )))
        ->render();