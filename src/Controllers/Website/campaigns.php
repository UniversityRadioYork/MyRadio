<?php
/**
 * List Campaigns
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130807
 * @package MyURY_Website
 */

if (!isset($_REQUEST['bannerid'])) {
  throw new MyURYException('You must provide a bannerid', 400);
}

$banner = MyURY_Banner::getInstance($_REQUEST['bannerid']);

CoreUtils::getTemplateObject()->setTemplate('Website/campaigns.twig')->addVariable('title', 'Banner Campaigns')
        ->addVariable('newcampaignurl', CoreUtils::makeURL('Website', 'createCampaign', ['bannerid' => $_REQUEST['bannerid']]))
        ->addVariable('bannersurl', CoreUtils::makeURL('Website', 'banners'))
        ->addVariable('bannerName', $banner->getAlt())
        ->addVariable('tabledata', CoreUtils::dataSourceParser($banner->getCampaigns()))
        ->addVariable('tablescript', 'myury.website.campaignlist')
        ->render();