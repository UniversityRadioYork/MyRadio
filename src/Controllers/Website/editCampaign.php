<?php
/**
 * Edit a Campaign
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130808
 * @package MyRadio_Website
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_BannerCampaign::getForm()->readValues();

    $campaign = MyRadio_BannerCampaign::getInstance($data['id']);

    $campaign->clearTimeslots();

    foreach ($data['timeslots'] as $timeslot) {
        $campaign->addTimeslot($timeslot['day'], $timeslot['start_time'], $timeslot['end_time']);
    }

    $campaign->setEffectiveFrom($data['effective_from'])
        ->setEffectiveTo($data['effective_to'])
        ->setLocation($data['location']);

    CoreUtils::redirect(
        'Website',
        'campaigns',
        [
            'bannerid' => $campaign->getBanner()->getBannerID(),
            'message' => base64_encode('The Campaign was updated successfully!')
        ]
    );

} else {
    //Not Submitted
    if (!isset($_REQUEST['campaignid'])) {
        throw new MyRadioException('You must provide a campaignid', 400);
    }

    $campaign = MyRadio_BannerCampaign::getInstance($_REQUEST['campaignid']);
    $campaign->getEditForm()->render([
        'campaignStart'=> CoreUtils::happyTime($campaign->getEffectiveFrom()),
        'bannerName'=> $campaign->getBanner()->getAlt()
    ]);
}
