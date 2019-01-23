<?php
/**
 * List all Podcasts.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\ServiceAPI\MyRadio_Podcast;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.podcasts')
    ->addVariable('title', 'Podcasts')
    ->addVariable('subtitle', 'All Podcasts')
    ->addVariable(
        'tabledata',
        CoreUtils::setToDataSource(MyRadio_Podcast::getAllPodcasts())
    )->render();
