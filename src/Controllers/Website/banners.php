<?php
/**
 * Main page for Banner Admin. Lists all the existing banners.
 *
 * @package MyRadio_Website
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Banner;

CoreUtils::getTemplateObject()->setTemplate('Website/banners.twig')->addVariable('title', 'Website Banners')
    ->addVariable('newbannerurl', URLUtils::makeURL('Website', 'editBanner'))
    ->addVariable('tabledata', CoreUtils::dataSourceParser(MyRadio_Banner::getAllBanners()))
    ->addVariable('tablescript', 'myradio.website.bannerlist')
    ->render();
