<?php
/**
 * Main page for Banner Admin. Lists all the existing banners.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130807
 * @package MyRadio_Website
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Banner;

CoreUtils::getTemplateObject()->setTemplate('Website/banners.twig')->addVariable('title', 'Website Banners')
    ->addVariable('newbannerurl', CoreUtils::makeURL('Website', 'editBanner'))
    ->addVariable('tabledata', CoreUtils::dataSourceParser(MyRadio_Banner::getAllBanners()))
    ->addVariable('tablescript', 'myury.website.bannerlist')
    ->render();
