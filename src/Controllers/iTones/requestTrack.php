<?php

/**
 * Allows a User to request a track on the jukebox
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyRadio_iTones
 */

MyRadio_JsonFormLoader::loadFromModule(
    $module,
    'requesttrackfrm',
    'requestTrack',
    [ 'remaining_requests' => iTones_Utils::getRemainingRequests()
    ]
)->render();
