<?php
/**
 * Allows creation of new URY members!
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130717
 * @package MyRadio_Profile
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $params = MyRadio_User::getQuickAddForm()->readValues();
    $user = MyRadio_User::create(
        $params['fname'],
        $params['sname'],
        $params['eduroam'],
        $params['sex'],
        $params['collegeid'],
        null,
        $params['phone']
    );

    CoreUtils::backWithMessage('New Member has been created with ID '.$user->getID());

} else {
    //Not Submitted
    MyRadio_User::getQuickAddForm()->render();
}
