<?php
/**
 * Main page for Banner Admin. Lists all the existing banners.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130806
 * @package MyURY_Website
 */

CoreUtils::getTemplateObject()->setTemplate('MyURY/text.twig')->addVariable('title', 'Website Banners')
        ->addVariable('newbannerurl', CoreUtils::makeURL('Website', 'createBanner'))
        ->addVariable('tabledata', MyURY_Banner::getAllBanners())
        ->addVariable('tablescript', 'myury.datatable.default')
        ->render();