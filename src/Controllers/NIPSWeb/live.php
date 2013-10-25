<?php

/**
 * Main renderer for NIPSWeb in LIVE mode.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130907
 * @package MyURY_NIPSWeb
 */
$template = 'NIPSWeb/live.twig';
$title = 'Broadcasting and Presenting Suite';
$reslists = CoreUtils::dataSourceParser(array(
            'managed' => iTones_Playlist::getAlliTonesPlaylists(),
            'auto' => NIPSWeb_AutoPlaylist::getAllAutoPlaylists(),
            'aux' => NIPSWeb_ManagedPlaylist::getAllManagedPlaylists(),
            'user' => NIPSWeb_ManagedUserPlaylist::getAllManagedUserPlaylistsFor(User::getInstance())
        ));

CoreUtils::getTemplateObject()->setTemplate($template)
        ->addVariable('title', $title)
        ->addVariable('tracks', [])//(new BRA_Utils())->getAllChannelInfo())
        ->addVariable('reslists', $reslists)
        ->render();