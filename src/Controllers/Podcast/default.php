<?php
/**
 * List a User's Podcasts.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Podcast;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.podcasts')
    ->addVariable('title', 'Podcasts')
    ->addVariable('subtitle', 'My Podcasts')
    ->addVariable(
        'tabledata',
        CoreUtils::setToDataSource(MyRadio_Podcast::getPodcastsAttachedToUser())
    )->render();
