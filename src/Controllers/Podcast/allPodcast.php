<?php
/**
 * List all Podcasts.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\ServiceAPI\MyRadio_Podcast;

AuthUtils::requirePermission(AUTH_EDITALLPODCASTS);

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.podcasts')
    ->addVariable('title', 'Podcasts')
    ->addVariable('title', 'All Podcasts')
    ->addVariable(
        'tabledata',
        CoreUtils::setToDataSource(MyRadio_Podcast::getAllPodcasts(), false)
    )->render();
