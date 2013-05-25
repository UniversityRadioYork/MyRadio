<?php
/**
 * Main renderer for NIPSWeb
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130525
 * @package MyURY_NIPSWeb
 */
require 'Views/bootstrap.php';

$twig->setTemplate('NIPSWeb/manage_library.twig')
        ->addVariable('reslists', CoreUtils::dataSourceParser(array(
            'managed' => array(),
            'aux' => NIPSWeb_ManagedPlaylist::getAllManagedPlaylists(true),
            'user' => NIPSWeb_ManagedUserPlaylist::getAllManagedUserPlaylistsFor(User::getInstance())
        )))
        ->render();