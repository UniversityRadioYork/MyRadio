<?php
/**
 * Allows Users to edit their profiles, or for admins to edit other users.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130715
 * @package MyRadio_Profile
 */

// Set if trying to view another member's profile page
if (isset($_REQUEST['profileedit-memberid']) && CoreUtils::hasPermission(AUTH_EDITANYPROFILE)) {
    $user = MyRadio_User::getInstance($_REQUEST['profileedit-memberid']);

} elseif (isset($_REQUEST['memberid']) && CoreUtils::hasPermission(AUTH_EDITANYPROFILE)) {
    $user = MyRadio_User::getInstance($_REQUEST['memberid']);

} else {
    $user = MyRadio_User::getInstance();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = $user->getEditForm()->readValues();

    $user->setFName($data['fname'])
        ->setSName($data['sname'])
        ->setSex($data['sex'])
        ->setCollegeID($data['collegeid'])
        ->setPhone($data['phone'])
        ->setEmail($data['email'])
        ->setReceiveEmail($data['receive_email'])
        ->setEduroam($data['eduroam'])
        ->setBio($data['bio']);

    if (!empty($data['photo']['tmp_name'])) {
        $user->setProfilePhoto(MyRadio_Photo::create($data['photo']['tmp_name']));
    }

    if (isset($data['local_name'])) {
        $user->setLocalName($data['local_name'])
            ->setLocalAlias($data['local_alias']);
    }

    CoreUtils::backWithMessage('User Updated');

} else {
    //Not Submitted
    $user->getEditForm()->render();
}
