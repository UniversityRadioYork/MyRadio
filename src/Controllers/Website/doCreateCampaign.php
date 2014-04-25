<?php
/**
 * Edit a Campaign
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130809
 * @package MyRadio_Website
 */

$data = MyRadio_BannerCampaign::getBannerCampaignForm()->readValues();

$campaign = MyRadio_BannerCampaign::create(
    MyRadio_Banner::getInstance($data['bannerid']),
    $data['location'],
    $data['effective_from'],
    $data['effective_to'],
    $data['timeslots']
);

CoreUtils::redirect(
    'Website',
    'editCampaign',
    ['campaignid' => $campaign->getID(), 'message' => base64_encode('The new Campaign was created successfully!')]
);
