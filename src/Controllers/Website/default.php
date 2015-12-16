<?php
/**
 * Landing page for Website Tools.
 */
use \MyRadio\MyRadio\CoreUtils;

CoreUtils::getTemplateObject()->setTemplate('MyRadio/text.twig')->addVariable('title', 'Website Tools')
    ->addVariable(
        'text',
        'This section of MyRadio lets you control some aspects of the Website, such as banners and themes.'
    )->render();
