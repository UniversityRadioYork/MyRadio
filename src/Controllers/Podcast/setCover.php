<?php

/**
 * Allows a podcast's cover to be set.
 *
 * @author  Matt Windsor <mattbw@ury.org.uk>
 * @version 20140117
 * @package MyRadio_Podcasts
 */

if (!isset($_REQUEST['podcast'])) {
    throw new MyRadioException('Podcast ID was not provided.', 400);
}

$podcast = MyRadio_Podcast::getInstance($_REQUEST['podcast']);

if (!currentUserCanEditPodcast($podcast)) {
    CoreUtils::requirePermission(AUTH_PODCASTANYSHOW);
}

MyRadio_JsonFormLoader::loadFromModule(
    $module, 'setCover', 'doSetCover'
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
