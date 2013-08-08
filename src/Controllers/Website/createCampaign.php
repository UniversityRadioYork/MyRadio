<?php
/**
 * Create a Campaign
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130808
 * @package MyURY_Website
 */
if (!isset($_REQUEST['bannerid'])) {
  throw new MyURYException('You must provide a bannerid', 400);
}

$banner = MyURY_Banner::getInstance($_REQUEST['bannerid']);

MyURY_BannerCampaign::getBannerCampaignForm($banner->getID())->render([
    bannerName => $banner->getAlt()
]);