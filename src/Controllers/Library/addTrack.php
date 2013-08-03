<?php
CoreUtils::getTemplateObject()->setTemplate('MyURY/text.twig')
        ->addVariable('text', '<iframe src="'.CoreUtils::makeURL('NIPSWeb','manage_library').'"></iframe>')
        ->render();