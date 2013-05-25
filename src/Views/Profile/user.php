<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Profile
 */
require 'Views/Profile/bootstrap.php';

foreach ($userData['training'] as $k => $v) {
  $userData['training'][$k]['confirmedbyurl'] = CoreUtils::makeURL('Profile', 'view', array('memberid' => $v['confirmedby']));
}

$twig->setTemplate('Profile/user.twig')
        ->addVariable('title', 'View Member')
        ->addVariable('title', 'View Profile')
        ->addVariable('user', $userData)
        // @todo User.php class needs more to give twig more.
        ->render();