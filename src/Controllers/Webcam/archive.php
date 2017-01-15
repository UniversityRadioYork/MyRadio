<?php
/**
 * Controller for viewing webcam archives.
 */
use \MyRadio\MyRadio\CoreUtils;

CoreUtils::getTemplateObject()->setTemplate('MyRadio/text.twig')
        ->addVariable('title', 'Webcams Archive')
        ->addInfo('Still coming soon to a MyRadio near you...')
        ->render();
