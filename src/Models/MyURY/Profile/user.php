<?php
/**
 * @todo Proper Documentation
 * @todo Probably make everything transport via arrays
 * @author Andy Durant <aj@ury.org.uk>
 * @version 26102012
 * @package MyURY_Profile
 */

$user = User::getInstance();

$name = $user->getName();
$sex = $user->getSex();
$collegeid = $user->getCollegeID();
$college = $user->getCollege();
$phone = $user->getPhone();
$uni = $user->getUniAccount();
$email = $user->getEmail();
$local_alias = $user->getLocalAlias();
$local_account = $user->getLocalName();
$receive_email = $user->getReceiveEmail();
$account_locked = $user->getAccountLocked();


/**
        ->addVariable('sex', $sex)
        ->addVariable('collegeid', $collegeid)
        ->addVariable('college', $college)
        ->addVariable('phone', $phone)
        ->addVariable('uni', $uni)
        ->addVariable('email', $email)
        ->addVariable('local_alias', $local_alias)
        ->addVariable('local_account', $local_account)
        ->addVariable('receive_email', $receive_email)
        ->addVariable('account_locked', $account_locked)
        ->addVariable('last_login', $last_login)
 * 
 */