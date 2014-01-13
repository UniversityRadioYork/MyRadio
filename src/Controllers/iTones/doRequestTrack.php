<?php

/**
 * Allows a User to request a track on the jukebox
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @author Matt Windsor <mattbw@ury.org.uk>
 * @version 20140112
 * @package MyRadio_iTones
 */

$data = MyRadio_JsonFormLoader::loadFromModule(
    $module,
    'requesttrackfrm',
    'doRequestTrack',
    [ 'remaining_requests' => iTones_Utils::getRemainingRequests()
    ]
)->readValues();

$success = iTones_Utils::requestTrack($data['track']);
if ($success === true) {
    $message = 'Track request submitted.';
} else {
    $message = 'Sorry, but this track cannot be requested right now.'
    . ' Please try again later.';
}

CoreUtils::backWithMessage($message);
