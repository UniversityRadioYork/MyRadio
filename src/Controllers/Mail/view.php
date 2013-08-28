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

CoreUtils::getTemplateObject()->setTemplate('MyURY/text.twig')
        ->addVariable('title', $email->getSubject())
        ->addVariable('text', '<a href="'.$_SERVER['HTTP_REFERER'].'">Back</a><hr>'.
                $email->getViewableBody())
        ->render();