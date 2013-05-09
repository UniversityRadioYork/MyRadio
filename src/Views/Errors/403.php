<?php
/**
 * This View renders a HTTP/1.1 403 Error - for when a <code>CoreUtils::requirePermission()</code> call returns false.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 */
header('HTTP/1.1 403 Forbidden');

$twig->setTemplate('error.twig')
        ->addVariable('serviceName', 'Error')
        ->addVariable('title', 'Forbidden')
        ->addVariable('heading', 'Access Denied')
        ->addVariable('body', '<p>I\'m sorry, but the Action, Module or Service you are trying to use requires elevated permissions you do not have.</p>
          <ul>
            <li>If you think you should have these permissions but do not, please contact Station Management.</li>
            <li>If you are Station Management, please contact Computing.</li>
            <li>If you are Computing, panic.</li>
          </ul>
          <details><summary>Detailed Request Information</summary>
          Error: HTTP/1.1 403: Forbidden<br>
          Service Requested: '.$service.'<br>
          Module Requested: '.$module.'<br>
          Action Requested: '.$action.'<br>
          User Requesting: '.(class_exists('User') ? (User::getInstance()->getName()) : '').'
          </details>')
        ->render();