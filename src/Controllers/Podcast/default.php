<?php
/**
 * List a User's Podcasts
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130815
 * @package MyRadio_Podcast
 */

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myury.datatable.default')
    ->addVariable('title', 'My Podcasts')
    ->addVariable(
        'tabledata',
        ServiceAPI::setToDataSource(
            MyRadio_Podcast::getPodcastsAttachedToUser(),
            false
        )
    )->render();
