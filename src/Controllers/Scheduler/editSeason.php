<?php
/**
 * This page enables Users to create a new Season or to edit a Season that already exists.
 * It can take one parameter, $_REQUEST['seasonid']
 * which should be the ID of the Show to edit.
 */
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Scheduler;
use \MyRadio\ServiceAPI\MyRadio_Season;
use \MyRadio\ServiceAPI\MyRadio_Show;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Season::getForm()->readValues();

    if (empty($data['id'])) {
        //create new
        MyRadio_Season::create($data);
        URLUtils::redirectWithMessage(
            'Scheduler',
            'myShows',
            "Your new season has been created. You should recieve an email when it's scheduled!"
        );
    } else {
        //submit edit
        $season = MyRadio_Season::getInstance($data['id']);

        //Check the user has permission to edit this show
        if (!$season->getShow()->isCurrentUserAnOwner()) {
            AuthUtils::requirePermission(AUTH_EDITSHOWS);
        }

        $season->setMeta('title', $data['title']);
        $season->setMeta('description', $data['description']);
        $season->setMeta('tag', explode(' ', $data['tags']));
        $season->setCredits($data['credits']['member'], $data['credits']['credittype']);
        if (!empty($data['subtype'])) {
            $season->setSubtypeByName($data['subtype']);
        } else {
            $season->clearSubtype();
        }

        URLUtils::redirectWithMessage(
            'Scheduler',
            'listSeasons',
            'Season updated!',
            ['showid' => $season->getShow()->getID()]
        );
    }
} else {
    //Not Submitted
    if (isset($_REQUEST['seasonid'])) {
        //edit form
        $season = MyRadio_Season::getInstance($_REQUEST['seasonid']);

        //Check the user has permission to edit this show
        if (!$season->getShow()->isCurrentUserAnOwner()) {
            AuthUtils::requirePermission(AUTH_EDITSHOWS);
        }

        $season->getEditForm()->render();
    } else {
        //create form

        $current_term_info = MyRadio_Scheduler::getActiveApplicationTermInfo();
        $current_term = $current_term_info['descr'];

        MyRadio_Season::getForm()
            ->setFieldValue('show_id', (int) $_REQUEST['showid'])
            ->setTemplate('Scheduler/createSeason.twig')
            ->render(
                [
                'current_term' => $current_term,
                'show_title' => MyRadio_Show::getInstance($_REQUEST['showid'])->getMeta('title'),
                ]
            );
    }
}
