<?php
/**
 * This page enables Users to create a new Show or edit a Show that already exists.
 * It can take one parameter, $_REQUEST['showid']
 * which should be the ID of the Show to edit.
 *
 * @package MyRadio_Scheduler
 */

use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Show;
use \MyRadio\ServiceAPI\MyRadio_User;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Show::getForm()->readValues();

    if (empty($data['id'])) {
        //create new
        $show = MyRadio_Show::create($data);
        CoreUtils::redirectWithMessage('Scheduler', 'myShows', 'Your show, ' . $show->getMeta('title') . ', has been created!');
    } else {
        //submit edit
        $show = MyRadio_Show::getInstance($data['id']);

        //Check the user has permission to edit this show
        if (!$show->isCurrentUserAnOwner()) {
            AuthUtils::requirePermission(AUTH_EDITSHOWS);
        }

        $show->setMeta('title', $data['title']);
        $show->setMeta('description', $data['description']);

        // We want to handle the case when people delimit with commas, or commas and
        // spaces, as well as handling extended spaces.
        $show->setMeta(
            'tag',
            preg_split('/[, ] */', $data['tags'], null, PREG_SPLIT_NO_EMPTY)
        );

        $show->setGenre($data['genres']);
        $show->setCredits($data['credits']['member'], $data['credits']['credittype']);

        if ($data['mixclouder']) {
            $show->setMeta('upload_state', 'Requested');
        } else {
            $show->setMeta('upload_state', 'Opted Out');
        }
        CoreUtils::backWithMessage("Show Updated!");
    }

} else {
    //Not Submitted
    if (isset($_REQUEST['showid'])) {
        //edit form
        $show = MyRadio_Show::getInstance($_REQUEST['showid']);

        //Check the user has permission to edit this show
        if (!$show->isCurrentUserAnOwner()) {
            AuthUtils::requirePermission(AUTH_EDITSHOWS);
        }

        $meta = $show->getMeta('tag');
        if ($meta === null) {
            $meta = [];
        }
        $show->getEditForm()->render();

    } else {
        //create form
        MyRadio_Show::getForm()
            ->setFieldValue('credits.member', [MyRadio_User::getInstance()])
            ->setFieldValue('credits.credittype', [1])
            ->setTemplate('Scheduler/createShow.twig')
            ->render();
    }
}
