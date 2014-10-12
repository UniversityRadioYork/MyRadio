<?php
/**
 * List of iTones_Playlists
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyRadio_iTones
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\iTones\iTones_Playlist;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('title', 'Campus Jukebox Playlists')
        ->addVariable('tabledata', CoreUtils::dataSourceParser(iTones_Playlist::getAlliTonesPlaylists()))
        ->addVariable('tablescript', 'myury.datatable.default')
        ->render();
