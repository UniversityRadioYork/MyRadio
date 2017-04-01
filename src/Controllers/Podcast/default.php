<?php
/**
 * List a User's Podcasts.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Podcast;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.podcasts')
    ->addVariable('title', 'My Podcasts')
    ->addVariable(
        'tabledata',
        CoreUtils::setToDataSource(MyRadio_Podcast::getPodcastsAttachedToUser(), false)
    )->render();
