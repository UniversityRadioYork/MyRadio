<?php
/**
 * List of iTones_Playlists.
 */

use MyRadio\iTones\iTones_PlaylistCategory;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\iTones\iTones_Playlist;

if (isset($_GET['category'])) {
    $category = (int) $_GET['category'];
} else {
    $category = 1;
}

CoreUtils::getTemplateObject()->setTemplate('iTones/listPlaylists.twig')
        ->addVariable('title', 'Campus Jukebox Playlists')
        ->addVariable('tabledata', CoreUtils::dataSourceParser(iTones_Playlist::getAllPlaylistsOfCategory($category)))
        ->addVariable('tablescript', 'myradio.iTones.listPlaylists')
        ->addVariable('category', $category)
        ->addVariable('allCategories', iTones_PlaylistCategory::getAll())
        ->render();
