<?php
/**
 * Lists the archive for a Mailing List
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130828
 * @package MyRadio_Mail
 * @todo Uses unsanitised HTTP_REFERER
 */

$email = MyRadioEmail::getInstance($_REQUEST['emailid']);

if (!$email->isRecipient(MyRadio_User::getInstance())) {
  throw new MyRadioException('You can only view emails you are a recipient of.', 403);
}

CoreUtils::getTemplateObject()->setTemplate('MyRadio/text.twig')
        ->addVariable('title', $email->getSubject())
        ->addVariable('text', '<a href="'.$_SERVER['HTTP_REFERER'].'">Back</a><hr>'.
                $email->getViewableBody())
        ->render();
