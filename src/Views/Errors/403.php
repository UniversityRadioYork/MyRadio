<?php
/**
 * This View renders a HTTP/1.1 403 Error - for when a <code>AuthUtils::requirePermission()</code> call returns false.
 */
use \MyRadio\MyRadio\CoreUtils;

header('HTTP/1.1 403 Forbidden');

CoreUtils::getTemplateObject()->setTemplate('error.twig')
    ->addVariable('serviceName', 'Error')
    ->addVariable('title', 'Forbidden')
    ->addVariable(
        'body',
        '<p>I\'m sorry, but you don\'t have permission to go here.</p>'
        .(empty($message)
            ? '<ul>
            <li>If you think you should be able to access this page, please contact Station Management.</li>
            <li>If you are Station Management, please contact Computing.</li>
            <li>If you are Computing, panic.</li>
            </ul>'
            : $message
        )
    )
    ->render();
