<?php

$twig = CoreUtils::getTemplateObject();
$twig->addVariable('serviceName', 'MyURY');
print_r($_SESSION);