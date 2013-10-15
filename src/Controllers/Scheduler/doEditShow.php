<?php
/**
 * This action handles the submission of a form from Scheduler/editShow. Uses standard Forms API for values.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130810
 * @package MyURY_Scheduler
 */

//Get the Form data
require 'Models/Scheduler/showfrm.php';
$data = $form->readValues();

//Check the user has permission to edit this show
$show = MyURY_Show::getInstance($data['id']);
if (!$show->isCurrentUserAnOwner() && !CoreUtils::hasPermission(AUTH_EDITSHOWS)) {
  $message = 'You must be a Creditor of a Show or be in the Programming Team to edit this show.';
  require 'Views/Errors/403.php';
}

$show->setMeta('title', $data['title']);
$show->setMeta('description', $data['description']);
$show->setMeta('tag', explode(' ', $data['tags']));
$show->setGenre($data['genres']);
$show->setCredits($data['credits']['member'], $data['credits']['credittype']);

CoreUtils::redirect(
  'Scheduler',
  'myShows',
  [
    'message' => base64_encode('Show updated')
  ]
);
