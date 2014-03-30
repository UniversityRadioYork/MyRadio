<?php
CoreUtils::getTemplateObject()->setTemplate('MyRadio/text.twig')
        ->addVariable('text', '<iframe src="'.CoreUtils::makeURL('NIPSWeb','manage_library').'" style="width:800px;border:none;height:1000px;margin:auto"></iframe>')
        ->render();
