<?php
/**
 * If you don't know what a 403 page is, you have a long way to go.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

$twig = CoreUtils::getTemplateObject();
header('HTTP/1.1 400 Bad Request');

CoreUtils::getTemplateObject()->setTemplate('error.twig')
    ->addVariable('serviceName', 'Error')
    ->addVariable('title', 'Bad Request')
    ->addVariable(
        'body',
        '<p>I\'m sorry, but the Action you are trying to perform needs more information from you.</p>'
        .(empty($message) ? '' : $message)
        .'<details><summary>Detailed Request Information</summary>
        Error: HTTP/1.1 400: Bad Request<br>
        Module Requested: '.$module.'<br>
        Action Requested: '.$action.'<br>
        User Requesting: '
        .(class_exists('MyRadio_User') ? (MyRadio_User::getInstance()->getName()) : '')
        .'</details>'
    )
    ->render();
exit;
