<?php
CoreUtils::getTemplateObject()->setTemplate('MyURY/text.twig')
        ->addVariable('text', '<iframe src="'.CoreUtils::makeURL('NIPSWeb','manage_library').'" style="width:800px;border:none;height:1000px;margin:auto"></iframe>')
        ->render();