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

CoreUtils::getTemplateObject()->setTemplate('Website/campaigns.twig')->addVariable('title', 'Banner Campaigns')
        ->addVariable('newcampaignurl', CoreUtils::makeURL('Website', 'createCampaign', ['bannerid' => $_REQUEST['bannerid']]))
        ->addVariable('tabledata', CoreUtils::dataSourceParser(MyURY_Banner::getInstance($_REQUEST['bannerid'])->getCampaigns()))
        ->addVariable('tablescript', 'myury.website.campaignlist')
        ->render();