<?php
/**
 * Common functions for the podcast controllers.
 *
 * @author  Matt Windsor <matt.windsor@ury.org.uk>
 * @version 20140117
 * @package MyRadio_Podcast
 */

/**
 * Loads the podcast cover form.
 *
 * @return MyRadioForm  The form.
 */
function podcastCoverForm()
{
    return MyRadio_JsonFormLoader::loadFromModule(
        'Podcast',
        'setCover',
        'doSetCover'
    );
}

/**
 * Gets the Podcast this form concerns.
 *
 * @param array $source  The parameters array; $_REQUEST by default.
 *
 * @return MyRadio_Podcast  The podcast.
 */
function currentPodcast($source = null)
{
    if ($source === null) {
        $source = $_REQUEST;
    }

    if (!array_key_exists('podcastid', $source)) {
        throw new MyRadioException('Podcast ID was not provided.', 400);
    }

    return MyRadio_Podcast::getInstance($source['podcastid']);
}

/**
 * Requires extra permissions if the current user cannot directly edit the
 * given podcast.
 *
 * @param MyRadio_Podcast $podcast  The podcast.
 */
function raisePermissionsIfCannotEdit($podcast)
{
    if (!currentUserCanEditPodcast($podcast)) {
        CoreUtils::requirePermission(AUTH_PODCASTANYSHOW);
    }
}

/**
 * Decides if the current user has edit rights to a podcast.
 *
 * @param MyRadio_Podcast $podcast  The podcast to query.
 *
 * @return boolean  True if the user can edit this podcast; false otherwise.
 */
function currentUserCanEditPodcast($podcast)
{
    // Doing this by ID saves having to query for all of the user's podcasts.
    $user_podcast_ids = MyRadio_Podcast::getPodcastIDsAttachedToUser();

    return in_array($podcast->getID(), $user_podcast_ids);
}
