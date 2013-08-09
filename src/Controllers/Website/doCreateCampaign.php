<?php
/**
 * Edit a Campaign
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130809
 * @package MyURY_Website
 */

$data = MyURY_BannerCampaign::getBannerCampaignForm()->readValues();

$campaign = MyURY_BannerCampaign::create(MyURY_Banner::getInstance($data['bannerid']),
        $data['location'], $data['effective_from'], $data['effective_to'], $data['timeslots']);

header('Location: '.CoreUtils::makeURL('Website', 'editCampaign', [
    'campaignid' => $campaign->getID(),
    'message' => base64_encode('The new Campaign was created successfully!')
]));