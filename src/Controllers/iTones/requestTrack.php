<?php

/**
 * Allows a User to request a track on the jukebox
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @author Matt Windsor <mattbw@ury.org.uk>
 * @version 20140112
 * @package MyRadio_iTones
 */

MyRadio_JsonFormLoader::loadFromModule(
    $module,
    'requesttrackfrm',
    'doRequestTrack',
    [ 'remaining_requests' => iTones_Utils::getRemainingRequests()
    ]
)->render();
