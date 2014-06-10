<?php
/**
 * Main renderer for NIPSWeb
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20140610
 * @package MyRadio_NIPSWeb
 */

CoreUtils::getTemplateObject()->setTemplate('NIPSWeb/manage_library.twig')
    ->addVariable(
        'reslists',
        CoreUtils::dataSourceParser(
            [
                'managed' => [],
                'aux' => NIPSWeb_ManagedPlaylist::getAllManagedPlaylists(true),
                'user' => NIPSWeb_ManagedUserPlaylist::getAllManagedUserPlaylistsFor(MyRadio_User::getInstance())
            ]
        )
    )->addVariable(
        'AUTH_UPLOADMUSICMANUAL',
        true
    )->render();
