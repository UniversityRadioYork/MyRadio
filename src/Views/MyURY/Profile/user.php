<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Profile
 */
require 'Views/MyURY/Profile/bootstrap.php';


$twig->setTemplate('MyURY/Profile/user.twig')
        ->addVariable('title', 'View Member')
        ->addVariable('heading', 'View Profile')
        ->addVariable('name', $name)
        ->addVariable('sex', $sex)
        ->addVariable('college', $college)
        ->addVariable('phone', $phone)
        ->addVariable('uni', $uni)
        ->addVariable('email', $email)
        ->addVariable('local_alias', $local_alias)
        ->addVariable('local_account', $local_account)
        ->addVariable('receive_email', $receive_email)
        ->addVariable('account_locked', $account_locked)
        ->addVariable('last_login', $last_login)
        // @todo use an array for all this data
        // @todo use a separate array for years paid
        // @todo use an array for officerships held
        // @todo use an array for training status
        ->render();