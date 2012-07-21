<?php
header('HTTP/1.1 404 File Not Found');

$twig->setTemplate('error.twig')
        ->addVariable('serviceName', 'Error')
        ->addVariable('title', 'File Not Found')
        ->addVariable('heading', 'I\'m not the page you\'re looking for')
        ->addVariable('body', 'I\'m sorry, but the Action, Module or Service you are looking for doesn\'t seem to exist.')
        ->render();