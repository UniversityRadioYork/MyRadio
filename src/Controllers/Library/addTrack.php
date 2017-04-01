<?php

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;

CoreUtils::getTemplateObject()->setTemplate('MyRadio/text.twig')
    ->addVariable('title', 'Upload Track')
    ->addVariable(
        'text',
        '<iframe src="'
        .URLUtils::makeURL('NIPSWeb', 'manage_library')
        .'" style="width:570px;border:none;height:1000px;margin:auto"></iframe>'
    )->render();
