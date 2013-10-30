<?php

/**
 * Edit a Campaign
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130809
 * @package MyRadio_Website
 */
$data = MyRadio_BannerCampaign::getBannerCampaignForm()->readValues();

$campaign = MyRadio_BannerCampaign::getInstance($data['id']);

$campaign->clearTimeslots();

foreach ($data['timeslots'] as $timeslot) {
  $campaign->addTimeslot($timeslot['day'], $timeslot['start_time'], $timeslot['end_time']);
}

$campaign->setEffectiveFrom($data['effective_from'])
        ->setEffectiveTo($data['effective_to'])
        ->setLocation($data['location']);

header('Location: '.CoreUtils::makeURL('Website', 'campaigns', [
    'bannerid' => $campaign->getBanner()->getBannerID(),
    'message' => base64_encode('The Campaign was updated succesfully!')
    ]));