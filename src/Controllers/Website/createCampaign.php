<?php
/**
 * Create a Campaign
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130808
 * @package MyRadio_Website
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_BannerCampaign::getForm()->readValues();

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

} else {
    //Not Submitted
    if (!isset($_REQUEST['bannerid'])) {
        throw new MyRadioException('You must provide a bannerid', 400);
    }

    $banner = MyRadio_Banner::getInstance($_REQUEST['bannerid']);

    MyRadio_BannerCampaign::getForm($banner->getBannerID())->render([
        'bannerName' => $banner->getAlt()
    ]);
}
