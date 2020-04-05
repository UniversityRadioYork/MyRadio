<?php

/**
 * Main renderer for NIPSWeb in LIVE mode.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\iTones\iTones_Playlist;
use \MyRadio\NIPSWeb\NIPSWeb_AutoPlaylist;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedPlaylist;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedUserPlaylist;

$template = 'NIPSWeb/live.twig';
$title = 'Broadcasting and Presenting Suite';
$reslists = CoreUtils::dataSourceParser(
    [
        'managed' => iTones_Playlist::getAlliTonesPlaylists(),
        'auto' => NIPSWeb_AutoPlaylist::getAllAutoPlaylists(),
        'aux' => NIPSWeb_ManagedPlaylist::getAllManagedPlaylists(),
        'user' => NIPSWeb_ManagedUserPlaylist::getAllManagedUserPlaylists(),
    ]
);

CoreUtils::getTemplateObject()->setTemplate($template)
    ->addVariable('title', $title)
    ->addVariable('tracks', [])//(new BRA_Utils())->getAllChannelInfo())
    ->addVariable('reslists', $reslists)
    ->render();
