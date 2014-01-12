<?php
/**
 * This action handles the submission of a form from Scheduler/editSeason.
 * Uses standard Forms API for values.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130923
 * @package MyRadio_Scheduler
 */

//Get the Form data
require 'Models/Scheduler/showfrm.php';
$data = $form->readValues();

//Check the user has permission to edit this show
$season = MyRadio_Season::getInstance($data['id']);
if (!$season->isCurrentUserAnOwner() && !CoreUtils::hasPermission(AUTH_EDITSHOWS)) {
  $message = 'You must be a Creditor of the Show or be in the Programming Team to edit this season.';
  require 'Views/Errors/403.php';
}

$season->setMeta('title', $data['title']);
$season->setMeta('description', $data['description']);
$season->setMeta('tag', explode(' ', $data['tags']));
$season->setCredits($data['credits']['member'], $data['credits']['credittype']);

CoreUtils::redirect(
  'Scheduler',
  'listSeasons',
  [
    'showid' => $season->getShow()->getID(),
    'message' => base64_encode('Season updated')
  ]
);
