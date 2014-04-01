<?php
/**
 * List of iTones_Playlists
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130714
 * @package MyRadio_iTones
 */

CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('title', 'Playlist History')
        ->addVariable(
            'tabledata',
            CoreUtils::dataSourceParser(iTones_PlaylistRevision::getAllRevisions($_REQUEST['playlistid']))
        )
        ->addVariable('tablescript', 'myury.datatable.default')
        ->render();
