<?php

/**
 * Allow a user to suspend or unsuspend a podcast
 */

use MyRadio\MyRadioException;
use \MyRadio\MyRadio\AuthUtils;
use MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Podcast;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted

    // Get the right data
    try {
        $data = MyRadio_Podcast::getSuspendForm()->readValues();
    } catch (MyRadioException $e) {
        try {
            $data = MyRadio_Podcast::getUnsuspendForm()->readValues();
        } catch (MyRadioException $e) {
            throw new MyRadioException("Can't read suspend/unsuspend form values.");
        }
    }

    $podcast = MyRadio_Podcast::getInstance($data["podcast_id"]);

    // Check if the user can edit this podcast
    if (!in_array($podcast->getID(), MyRadio_Podcast::getPodcastIDsAttachedToUser())) {
        AuthUtils::requirePermission(AUTH_PODCASTANYSHOW);
    }

    // Request unsuspension or suspend podcast
    if ($podcast->isSuspended()) {
        if ($data["reason"] != "") {
            $podcast->requestUnsuspend($data["reason"]);
            $return_message = "Request Sent";
        } else {
            $return_message = "You need to provide a reason for requesting unsuspension";
        }
    } else {
        if (!$data["confirm"]) {
            $return_message = "You need to confirm the suspension.";
        } else {
            $podcast->setSuspended(true);
            $return_message = "Podcast Suspended";
        }
    }

    URLUtils::redirectWithMessage("Podcast", "default", $return_message);
} else {
    //Not Submitted
    if (isset($_REQUEST['podcast_id'])) {

        $podcast = MyRadio_Podcast::getInstance($_REQUEST['podcast_id']);

        // Check if user can suspend this podcast
        if (!in_array($podcast->getID(), MyRadio_Podcast::getPodcastIDsAttachedToUser())) {
            AuthUtils::requirePermission(AUTH_EDITANYPODCAST);
        }

        if (!$podcast->isSuspended()) {
            $podcast->getSuspendForm()->render();
        } else {
            $podcast->getUnsuspendForm()->render();
        }
    } else {
        throw new MyRadioException("Podcast ID needs specifying.");
    }
}
