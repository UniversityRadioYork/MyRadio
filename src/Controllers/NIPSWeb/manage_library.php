<?php
/**
 * Main renderer for NIPSWeb
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130525
 * @package MyRadio_NIPSWeb
 */
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
