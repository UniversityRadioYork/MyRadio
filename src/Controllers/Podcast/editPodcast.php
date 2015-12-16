<?php
/**
 * Render form to create a new Podcast.
 */
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Podcast;
use \MyRadio\ServiceAPI\MyRadio_Show;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Podcast::getForm()->readValues();

    if (empty($data['id'])) {
        //create new
        $podcast = MyRadio_Podcast::create(
            $data['title'],
            $data['description'],
            explode(' ', $data['tags']),
            $data['file']['tmp_name'],
            empty($data['show']) ? null : MyRadio_Show::getInstance($data['show']),
            $data['credits']
        );
    } else {
        //submit edit
        $podcast = MyRadio_Podcast::getInstance($data['id']);

        // Check if user can edit this podcast
        if (!in_array($podcast->getID(), MyRadio_Podcast::getPodcastIDsAttachedToUser())) {
            AuthUtils::requirePermission(AUTH_PODCASTANYSHOW);
        }

        $podcast->setMeta('title', $data['title'])
            ->setMeta('description', $data['description'])
            ->setMeta('tag', explode(' ', $data['tags']))
            ->setCredits($data['credits']['member'], $data['credits']['credittype']);

        if (!empty($data['show'])) {
            $podcast->setShow(MyRadio_Show::getInstance($data['show']));
        } else {
            $podcast->setShow(null);
        }
    }

    if (!empty($data['existing_cover'])) {
        $podcast->setCover($data['existing_cover']);
    } elseif (is_uploaded_file($data['new_cover']['tmp_name'])) {
        $podcast->createCover($data['new_cover']['tmp_name']);
    } else {
        throw new MyRadioException('Unknown cover upload method.', 400);
    }

    URLUtils::backWithMessage('Podcast Updated');
} else {
    //Not Submitted
    if (isset($_REQUEST['podcast_id'])) {
        //edit form
        $podcast = MyRadio_Podcast::getInstance($_REQUEST['podcast_id']);

        // Check if user can edit this podcast
        if (!in_array($podcast->getID(), MyRadio_Podcast::getPodcastIDsAttachedToUser())) {
            AuthUtils::requirePermission(AUTH_PODCASTANYSHOW);
        }

        $podcast
            ->getEditForm()
            ->render();
    } else {
        //create form
        MyRadio_Podcast::getForm()->render();
    }
}
