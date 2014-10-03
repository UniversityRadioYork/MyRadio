<?php

/**
 * Allows a User to request a track on the jukebox
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @author Matt Windsor <mattbw@ury.org.uk>
 * @version 20140112
 * @package MyRadio_iTones
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\MyRadioForm;
use \MyRadio\MyRadio\MyRadioFormField;
use \MyRadio\ServiceAPI\iTones_Utils;

$form = (
    new MyRadioForm(
        'itones_trackrequest',
        $module,
        $action,
        [
            'debug' => true,
            'title' => 'Request Campus Jukebox Track'
        ]
    )
)->addField(
    new MyRadioFormField(
        'track',
        MyRadioFormField::TYPE_TRACK,
        [
            'explanation' => 'Enter a track here to request it on the Jukebox.',
            'label' => 'Track'
        ]
    )
)->addField(
    new MyRadioFormField(
        'requests',
        MyRadioFormField::TYPE_NUMBER,
        [
            'explanation' => 'This is the number of requests you can make at the moment. '
                .'If you run out of requests, please wait a while and try again.',
            'label' => 'Remaining Requests',
            'value' => iTones_Utils::getRemainingRequests(),
            'enabled' => false,
            'required' => false
        ]
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = $form->readValues();

    $success = iTones_Utils::requestTrack($data['track']);
    if ($success === true) {
        $message = 'Track request submitted.';
    } else {
        $message = 'Sorry, but this track cannot be requested right now. Please try again later.';
    }

    CoreUtils::backWithMessage($message);

} else {
    //Not Submitted
    $form->render();
}
