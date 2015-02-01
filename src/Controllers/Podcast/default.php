<?php
/**
 * List a User's Podcasts
 *
 * @package MyRadio_Podcast
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\ServiceAPI;
use \MyRadio\ServiceAPI\MyRadio_Podcast;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.podcasts')
    ->addVariable('title', 'My Podcasts')
    ->addVariable(
        'tabledata',
        ServiceAPI::setToDataSource(
            MyRadio_Podcast::getPodcastsAttachedToUser(),
            false
        )
    )->render();
