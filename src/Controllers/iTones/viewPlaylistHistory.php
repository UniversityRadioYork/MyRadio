<?php
/**
 * List of iTones_Playlists
 *
 * @package MyRadio_iTones
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\iTones\iTones_PlaylistRevision;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('title', 'Playlist History')
        ->addVariable(
            'tabledata',
            CoreUtils::dataSourceParser(iTones_PlaylistRevision::getAllRevisions($_REQUEST['playlistid']))
        )
        ->addVariable('tablescript', 'myury.datatable.default')
        ->render();
