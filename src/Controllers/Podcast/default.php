<?php
/**
 * List a User's Podcasts
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130815
 * @package MyURY_Podcast
 */

CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.datatable.default')
        ->addVariable('title', 'My Podcasts')
        ->addVariable('tabledata', 
                ServiceAPI::setToDataSource(
                        MyURY_Podcast::getPodcastsAttachedToUser(), false))
        ->render();