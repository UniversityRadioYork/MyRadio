<?php
header('HTTP/1.1 403 Forbidden');

$twig->setTemplate('error.twig')
        ->addVariable('serviceName', 'Error')
        ->addVariable('title', 'Forbidden')
        ->addVariable('heading', 'Access Denied')
        ->addVariable('body', 'I\'m sorry, but the Action, Module or Service you are trying to use requires elevated permissions you do not have. If you think you should have these permissions but do not, please contact Station Management. If you are Station Management, please contact Computing. If you are Computing, panic.')
        ->render();