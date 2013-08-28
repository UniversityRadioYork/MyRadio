<?php
/**
 * Lists the archive for a Mailing List
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130828
 * @package MyURY_Mail
 * @todo Uses unsanitised HTTP_REFERER
 */

$email = MyURYEmail::getInstance($_REQUEST['emailid']);

if (!$email->isRecipient(User::getInstance())) {
  throw new MyURYException('You can only view emails you are a recipient of.', 403);
}

CoreUtils::getTemplateObject()->setTemplate('MyURY/text.twig')
        ->addVariable('title', $email->getSubject())
        ->addVariable('text', '<a href="'.$_SERVER['HTTP_REFERER'].'">Back</a><hr>'.
                $email->getViewableBody())
        ->render();