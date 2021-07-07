<?php

/**
 * List of all iTones_Playlists, whether archived or not.
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\iTones\iTones_Playlist;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'All Playlists')
    ->addVariable(
        'tabledata',
        CoreUtils::dataSourceParser(iTones_Playlist::getAlliTonesPlaylists($includeArchived = true))
    )
    ->addVariable('tablescript', 'myradio.iTones.allPlaylists')
    ->render();
