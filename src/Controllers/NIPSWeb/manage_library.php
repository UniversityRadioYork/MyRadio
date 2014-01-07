<?php
/**
 * Main renderer for NIPSWeb
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130525
 * @package MyRadio_NIPSWeb
 */
CoreUtils::getTemplateObject()->setTemplate('NIPSWeb/manage_library.twig')
        ->addVariable('reslists', CoreUtils::dataSourceParser(array(
            'managed' => array(),
            'aux' => NIPSWeb_ManagedPlaylist::getAllManagedPlaylists(true),
            'user' => NIPSWeb_ManagedUserPlaylist::getAllManagedUserPlaylistsFor(MyRadio_User::getInstance())
        )))
        ->render();