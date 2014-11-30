<?php
/**
 * Allows a User to edit an iTones PlaylistAvailability
 *
 * @package MyRadio_iTones
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\iTones\iTones_Playlist;
use \MyRadio\iTones\iTones_PlaylistAvailability;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = iTones_PlaylistAvailability::getForm()->readValues();

    if (empty($data['id'])) {
        //Create
        $availability = iTones_PlaylistAvailability::create(
            iTones_Playlist::getInstance($data['playlistid']),
            $data['weight'],
            $data['effective_from'],
            $data['effective_to'],
            $data['timeslots']
        );

        CoreUtils::redirect('iTones', 'editAvailability', [
            'availabilityid' => $availability->getID(),
            'message' => base64_encode('The availability has been created.')
        ]);
    } else {
        //Update
        $availability = iTones_PlaylistAvailability::getInstance($data['id']);

        $availability->setEffectiveFrom($data['effective_from']);
        $availability->setEffectiveTo($data['effective_to']);
        $availability->setWeight($data['weight']);
        $availability->clearTimeslots();
        foreach ($data['timeslots'] as $timeslot) {
            $availability->addTimeslot($timeslot['day'], $timeslot['start_time'], $timeslot['end_time']);
        }

        CoreUtils::backWithMessage('The availability has been updated.');
    }

} elseif (!empty($_REQUEST['availabilityid'])) {
    //Not Submitted, update
    $availability = iTones_PlaylistAvailability::getInstance($_REQUEST['availabilityid']);

    $availability->getEditForm()//->setTemplate('iTones/editAvailability.twig')
        ->render();
} else {
    //Not Submitted, create
    if (empty($_REQUEST['playlistid'])) {
        throw new MyRadioException('No Playlist ID provided.', 400);
    }

    iTones_PlaylistAvailability::getForm($_REQUEST['playlistid'])
        ->render();
}
