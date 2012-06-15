<?php
print_r($_SESSION);
$twig = CoreUtils::getTemplateObject();
$twig->addVariable('serviceName', 'MyURY');