<?php
/**
 * Main renderer for NIPSWeb
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130824
 * @package MyRadio_NIPSWeb
 */

CoreUtils::requireTimeslot();

if (isset($_REQUEST['readonly'])) {
  $template = 'NIPSWeb/readonly.twig';
  $title = MyRadio_Timeslot::getInstance($_SESSION['timeslotid'])->getMeta('title');
  $reslists = [];
} else {
  $template = 'NIPSWeb/main.twig';
  $title = 'Show Planner';
  $reslists = CoreUtils::dataSourceParser(array(
            'managed' => iTones_Playlist::getAlliTonesPlaylists(),
            'auto' => NIPSWeb_AutoPlaylist::getAllAutoPlaylists(),
            'aux' => NIPSWeb_ManagedPlaylist::getAllManagedPlaylists(),
            'user' => NIPSWeb_ManagedUserPlaylist::getAllManagedUserPlaylistsFor(MyRadio_User::getInstance())
        ));
}

CoreUtils::getTemplateObject()->setTemplate($template)
        ->addVariable('title', $title)
        ->addVariable('tracks', MyRadio_Timeslot::getInstance($_SESSION['timeslotid'])->getShowPlan())
        ->addVariable('reslists', $reslists)
        ->render();
