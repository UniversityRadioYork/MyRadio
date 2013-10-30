<?php
/**
 * Allows creation of new URY members!
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130717
 * @package MyRadio_Profile
 */
$params = User::getQuickAddForm()->readValues();
$user = User::create($params['fname'], $params['sname'], $params['eduroam'],
            $params['sex'], $params['collegeid'], null, $params['phone']);

CoreUtils::backWithMessage('New Member has been created with ID '.$user->getID());