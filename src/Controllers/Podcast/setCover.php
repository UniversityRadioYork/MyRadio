<?php

/**
 * Allows a podcast's cover to be set.
 *
 * @author  Matt Windsor <matt.windsor@ury.org.uk>
 * @version 20140117
 * @package MyRadio_Podcasts
 */

require_once 'common.php';

$podcast = currentPodcast();
raisePermissionsIfCannotEdit($podcast);

podcastCoverForm(
)->setFieldValue(
    'podcastid', $podcast->getID()
)->setFieldValue(
    'existing_cover', $podcast->getCover()
)->render();

//
// Helper functions
//

/**
 * Decides if the current user has edit rights to a podcast.
 *
 * @param MyRadio_Podcast $podcast  The podcast to query.
 *
 * @return boolean  True if the user can edit this podcast; false otherwise.
 */
function currentUserCanEditPodcast($podcast) {
    // Doing this by ID saves having to query for all of the user's podcasts.
    $user_podcast_ids = MyRadio_Podcast::getPodcastIDsAttachedToUser();
    return in_array($podcast->getID(), $user_podcast_ids);
}

?>
