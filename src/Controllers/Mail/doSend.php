<?php
/**
 * Send an email to a mailing list
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130526
 * @package MyRadio_Mail
 */

$info = MyRadio_JsonFormLoader::loadFromModule(
    $module, 'send', 'doSend'
)->readValues();

MyRadioEmail::sendEmailToList(MyRadio_List::getInstance($info['list']), $info['subject'], $info['body'], MyRadio_User::getInstance());

CoreUtils::backWithMessage('Message sent!');
