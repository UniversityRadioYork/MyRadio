<?php
/**
 * Edit a Banner
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130806
 * @package MyRadio_Website
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Banner::getForm()->readValues();

    $banner = MyRadio_Banner::getInstance($data['id'])
        ->setAlt($data['alt'])
        ->setTarget($data['target'])
        ->setType($data['type']);

    if ($data['photo']['error'] == 0) {
        //Upload replacement Photo
        $banner->setPhoto(MyRadioPhoto::create($data['photo']['tmp_name']));
    }

    CoreUtils::backWithMessage('The Banner was updated successfully!');

} else {
    //Not Submitted
    if (!isset($_REQUEST['bannerid'])) {
        throw new MyRadioException('You must provide a bannerid', 400);
    }

    $banner = MyRadio_Banner::getInstance($_REQUEST['bannerid']);
    $banner->getEditForm()->render(['bannerName' => $banner->getAlt()]);
}
