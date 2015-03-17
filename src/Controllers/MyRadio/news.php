<?php
/**
 * This is the controller for the news items
 * members news, tech news and the presenter information sheet
 *
 * @data    20131228
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\MyRadioNews;

CoreUtils::getTemplateObject()->setTemplate('MyRadio/news.twig')
        ->addVariable('title', 'News Feed')
        ->addVariable('tabledata', MyRadioNews::getFeed($container['database'], $_REQUEST['feed']))
        ->addVariable('tablescript', 'myradio.newslist')
        ->addVariable('feedid', $_REQUEST['feed'])
        ->render();
