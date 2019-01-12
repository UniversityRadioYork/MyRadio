<?php
/**
 * View a User's profile. There are different levels of information available:<br>
 * - Any member can view Name, College, Officership, Training status and photo of any other member
 * - Any member can also view Phone & email alias of any committee member
 * - Members with AUTH_VIEWOTHERMEMBERS can view eduroam/email/locked/last login/paid of any other member.
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
$mixins = [];

if ($user->getID() === $visitor->getID() || AuthUtils::hasPermission(AUTH_VIEWOTHERMEMBERS)) {
    $mixins = ['personal_data', 'officerships', 'payment'];
} elseif ($user->isOfficer()) {
    // A non-officer viewing an officer
    $mixins = ['officerships'];
}

$userData = $user->toDataSource($mixins);

$userData['training'] = CoreUtils::dataSourceParser($user->getAllTraining());
$userData['training_avail'] = CoreUtils::dataSourceParser(MyRadio_TrainingStatus::getAllAwardableTo($user));

// A non-officer viewing an officer
if ($user->isOfficer()) {
    $userData['phone'] = $user->getPhone();
}

$template = CoreUtils::getTemplateObject()->setTemplate('Profile/user.twig')
    ->addVariable('title', 'View Profile')
    ->addVariable('user', $userData);

if ($user->getID() === $visitor->getID() || $visitor->hasAuth(AUTH_EDITANYPROFILE)) {
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
