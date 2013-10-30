<?php

/**
 * Allows a User to request a track on the jukebox
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyRadio_iTones
 */
//The Form definition
require 'Models/iTones/requesttrackfrm.php';

$data = $form->readValues();
iTones_Utils::requestTrack($data['track']);
CoreUtils::backWithMessage('Your track request was sent to the legumes for review.');