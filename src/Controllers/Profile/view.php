<?php
/**
 * View a User's profile. There are different levels of information available:<br>
 * - Any member can view Name, Sex, College, Officership, Training status and photo of any other member
 * - Any member can also view Phone & email alias of any committee member
 * - Members with AUTH_VIEWOTHERMEMBERS can view eduroam/email/locked/last login/paid of any other member
 *
 * @package MyRadio_Profile
 */

use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\MyRadio_TrainingStatus;

// Set if trying to view another member's profile page
$user = MyRadio_User::getInstance(empty($_REQUEST['memberid']) ? -1 : $_REQUEST['memberid']);
$visitor = MyRadio_User::getInstance();

//Add global user data

if ($user->getID() === $visitor->getID() or AuthUtils::hasPermission(AUTH_VIEWOTHERMEMBERS)) {
    $userData = $user->toDataSource(['personal_data', 'officerships', 'payment']);
} else {
    $userData = $user->toDataSource();
}


$userData['training'] = CoreUtils::dataSourceParser($user->getAllTraining(true));
$userData['training_avail'] = CoreUtils::dataSourceParser(
    MyRadio_TrainingStatus::getAllAwardableTo($user)
);

if ($user->isOfficer()) {
    $userData['phone'] = $user->getPhone();
    $userData['email'] = $user->getPublicEmail();
    $userData['officerships'] = $user->getOfficerships();
}

if ($user->getID() === $visitor->getID() or AuthUtils::hasPermission(AUTH_VIEWOTHERMEMBERS)) {
    $userData['email'] = $user->getEmail();
    $userData['local_alias'] = $user->getLocalAlias();
    $userData['last_login'] = $user->getLastLogin();
    $userData['is_currently_paid'] = $user->isCurrentlyPaid();
}

$template = CoreUtils::getTemplateObject()->setTemplate('Profile/user.twig')
    ->addVariable('title', 'View Profile')
    ->addVariable('user', $userData);

if ($user->getID() === $visitor->getID() or $visitor->hasAuth(AUTH_EDITANYPROFILE)) {
    $template->addVariable(
        'editurl',
        '<a href="'
        .URLUtils::makeURL(
            'Profile',
            'edit',
            ['memberid' => $user->getID()]
        )
        .'">Edit Profile</a>'
    );
}
if (AuthUtils::hasPermission(AUTH_IMPERSONATE)
    && ($user->hasAuth(AUTH_BLOCKIMPERSONATE) === false
    || AuthUtils::hasPermission(AUTH_IMPERSONATE_BLOCKED_USERS))
) {
    $template->addVariable(
        'impersonateurl',
        '<a href="'
        .URLUtils::makeURL(
            'MyRadio',
            'impersonate',
            ['memberid' => $user->getID()]
        )
        .'">Impersonate User</a>'
    );
}
if (AuthUtils::hasPermission(AUTH_LOCK)) {
    $template->addVariable(
        'lockurl',
        '<a href="'
        .URLUtils::makeURL(
            'Profile',
            'lock',
            ['memberid' => $user->getID()]
        )
        .'">Disable Account</a>'
    );
}
if (AuthUtils::hasPermission(AUTH_MARKPAYMENT)) {
    $template->addVariable('can_mark_payments', true);
}
$template->render();
