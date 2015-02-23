<?php
/**
 * Main renderer for NIPSWeb
 *
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedPlaylist;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedUserPlaylist;

$template = 'NIPSWeb/manage_library.twig';
if (CoreUtils::hasPermission(AUTH_UPLOADMUSICMANUAL)) {
    $template = 'NIPSWeb/manage_library_manual.twig';
}

CoreUtils::getTemplateObject()->setTemplate($template)
    ->addVariable(
        'reslists',
        CoreUtils::dataSourceParser(
            [
                'managed' => [],
                'aux' => NIPSWeb_ManagedPlaylist::getAllManagedPlaylists(true),
                'user' => NIPSWeb_ManagedUserPlaylist::getAllManagedUserPlaylistsFor(MyRadio_User::getInstance())
            ]
        )
    )->render();
