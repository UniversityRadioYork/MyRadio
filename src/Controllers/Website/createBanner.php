<?php
/**
 * Create a Banner
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130809
 * @package MyRadio_Website
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Banner::getForm()->readValues();

    $photo = MyRadio_Photo::create($data['photo']['tmp_name']);

    $banner = MyRadio_Banner::create($photo, $data['alt'], $data['target'], $data['type']);

    CoreUtils::redirect(
        'Website',
        'campaigns',
        ['bannerid' => $banner->getBannerID(), 'message' => base64_encode('Your new Banner has been created!')]
    );

} else {
    //Not Submitted
    MyRadio_Banner::getForm()->render();
}
