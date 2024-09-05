<?php
/**
 * Allows Users to edit their profiles, or for admins to edit other users.
 */
use \MyRadio\Config;
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\MyRadio_Photo;

// Set if trying to view another member's profile page
if (isset($_REQUEST['profileedit-memberid']) && AuthUtils::hasPermission(AUTH_EDITANYPROFILE)) {
    $user = MyRadio_User::getInstance($_REQUEST['profileedit-memberid']);
} elseif (isset($_REQUEST['memberid']) && AuthUtils::hasPermission(AUTH_EDITANYPROFILE)) {
    $user = MyRadio_User::getInstance($_REQUEST['memberid']);
} else {
    $user = MyRadio_User::getInstance();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = $user->getEditForm()->readValues();

    $user->setFName($data['fname'])
        ->setNName($data['nname'])
        ->setSName($data['sname'])
        ->setCollegeID($data['collegeid'])
        ->setPhone($data['phone'])
        ->setEmail($data['email'])
        ->setReceiveEmail($data['receive_email'])
        ->setEduroam($data['eduroam'])
        ->setBio($data['bio'])
        ->setHideProfile($data['hide']);

    if ($data['data_removal']) {
        $user->setDataRemoval('optout');
    } else {
        $user->setDataRemoval('default');
    }

    if (!empty(Config::$contract_uri)) {
        $user->setContractSigned($data['contract']);
    }

    if (!empty($data['photo']['tmp_name'])) {
        $user->setProfilePhoto(MyRadio_Photo::create($data['photo']['tmp_name']));
    }

    if (isset($data['local_name'])) {
        $user->setLocalName($data['local_name'])
            ->setLocalAlias($data['local_alias']);
    }

    URLUtils::redirectWithMessage('Profile', 'view', 'User Updated');
} else {
    //Not Submitted
    $user->getEditForm()->render();
}
