<?php

/**
 * This provides similar information to listOfficers, but in a far nicer format.
 */
use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\Profile;

$officers = Profile::getOfficers();

foreach ($officers as $k => $v) {
    if (!empty($officers[$k]['name'])) {
        $officers[$k]['url'] = URLUtils::makeURL('Profile', 'view', ['memberid' => $v['memberid']]);
    }

    if (!empty($officers[$k]['memberid'])) {
        $image = MyRadio_User::getInstance($officers[$k]['memberid'])->getProfilePhoto();
        $officers[$k]['image'] = $image !== null ? $image->getURL() : Config::$default_person_uri;
    } else {
        $officers[$k]['image'] = Config::$vacant_officer_uri;
    }
}

CoreUtils::getTemplateObject()->setTemplate('Profile/officers.twig')
    ->addVariable('title', Config::$short_name.' Committee')
    ->addVariable('officers', $officers)
    ->render();
