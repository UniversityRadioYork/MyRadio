<?php
/**
 * Edit a Campaign
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130808
 * @package MyURY_Website
 */

if (!isset($_REQUEST['campaignid'])) {
  throw new MyURYException('You must provide a campaignid', 400);
}

$campaign = MyURY_BannerCampaign::getInstance($_REQUEST['campaignid']);
$campaign->getEditForm()->render([
    campaignStart=> CoreUtils::happyTime($campaign->getEffectiveFrom()),
    bannerName=> $campaign->getBanner()->getAlt()
]);