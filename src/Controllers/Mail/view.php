<?php
/**
 * Lists the archive for a Mailing List
 *
 * @package MyRadio_Mail
 * @todo    Uses unsanitised HTTP_REFERER
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadioEmail;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

$email = MyRadioEmail::getInstance($_REQUEST['emailid']);

if (!$email->isRecipient(MyRadio_User::getInstance())) {
    throw new MyRadioException('You can only view emails you are a recipient of.', 403);
}

CoreUtils::getTemplateObject()->setTemplate('MyRadio/text.twig')
    ->addVariable('title', $email->getSubject())
    ->addVariable(
        'text',
        '<a href="'.$_SERVER['HTTP_REFERER'].'">Back</a><hr>'
        .$email->getViewableBody()
    )->render();
