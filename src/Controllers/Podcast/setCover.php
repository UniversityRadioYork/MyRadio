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


