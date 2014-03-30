<?php
/**
 * Edit a Campaign
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130808
 * @package MyRadio_Website
 */

if (!isset($_REQUEST['campaignid'])) {
  throw new MyRadioException('You must provide a campaignid', 400);
}

$campaign = MyRadio_BannerCampaign::getInstance($_REQUEST['campaignid']);
$campaign->getEditForm()->render([
    'campaignStart'=> CoreUtils::happyTime($campaign->getEffectiveFrom()),
    'bannerName'=> $campaign->getBanner()->getAlt()
]);
