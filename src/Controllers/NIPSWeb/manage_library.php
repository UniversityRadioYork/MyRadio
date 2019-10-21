<?php
/**
 * Main renderer for NIPSWeb.
 */
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadioException;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedPlaylist;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedUserPlaylist;

if (!AuthUtils::hasPermission(AUTH_UPLOADMUSICMANUAL)) {
    throw new MyRadioException('You must have been Manual Upload trained before accessing the uploader. If you need to get trained, please contact the Head of Music.', 403);
}

CoreUtils::getTemplateObject()->setTemplate('NIPSWeb/manage_library_manual.twig')
    ->addVariable(
        'reslists',
        CoreUtils::dataSourceParser(
            [
                'managed' => [],
                'aux' => NIPSWeb_ManagedPlaylist::getAllManagedPlaylists(true),
                'user' => NIPSWeb_ManagedUserPlaylist::getAllManagedUserPlaylistsFor(MyRadio_User::getInstance()),
            ]
        )
    )->render();
