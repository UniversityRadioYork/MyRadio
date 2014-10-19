<?php
/**
 * This View renders a HTTP/1.1 403 Error - for when a <code>CoreUtils::requirePermission()</code> call returns false.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130809
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

header('HTTP/1.1 403 Forbidden');

CoreUtils::getTemplateObject()->setTemplate('error.twig')
    ->addVariable('serviceName', 'Error')
    ->addVariable('title', 'Forbidden')
    ->addVariable(
        'body',
        '<p>I\'m sorry, but the Action you are trying to perform requires elevated permissions you do not have.</p>
        <ul>
        <li>If you think you should have these permissions but do not, please contact Station Management.</li>
        <li>If you are Station Management, please contact Computing.</li>
        <li>If you are Computing, panic.</li>
        </ul>'
        .(empty($message) ? '' : $message)
        .'<details><summary>Detailed Request Information</summary>
        Error: HTTP/1.1 403: Forbidden<br>
        Module Requested: '.$module.'<br>
        Action Requested: '.$action.'<br>
        User Requesting: '
        .(class_exists('\MyRadio\ServiceAPI\MyRadio_User') ? (MyRadio_User::getInstance()->getName()) : 'Anonymous')
        .'</details>'
    )
    ->render();
