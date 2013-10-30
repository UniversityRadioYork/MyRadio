<?php
/**
 * Create a Campaign
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130808
 * @package MyRadio_Website
 */
if (!isset($_REQUEST['bannerid'])) {
  throw new MyRadioException('You must provide a bannerid', 400);
}

$banner = MyRadio_Banner::getInstance($_REQUEST['bannerid']);

MyRadio_BannerCampaign::getBannerCampaignForm($banner->getBannerID())->render([
    'bannerName' => $banner->getAlt()
]);