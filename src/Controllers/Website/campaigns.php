<?php
/**
 * List Campaigns
 *
 * @package MyRadio_Website
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Banner;

if (!isset($_REQUEST['bannerid'])) {
    throw new MyRadioException('You must provide a bannerid', 400);
}

$banner = MyRadio_Banner::getInstance($_REQUEST['bannerid']);

CoreUtils::getTemplateObject()->setTemplate('Website/campaigns.twig')->addVariable('title', 'Banner Campaigns')
    ->addVariable(
        'newcampaignurl',
        URLUtils::makeURL('Website', 'editCampaign', ['bannerid' => $_REQUEST['bannerid']])
    )->addVariable('bannersurl', URLUtils::makeURL('Website', 'banners'))
    ->addVariable('bannerName', $banner->getAlt())
    ->addVariable('tabledata', CoreUtils::dataSourceParser($banner->getCampaigns()))
    ->addVariable('tablescript', 'myury.website.campaignlist')
    ->render();
