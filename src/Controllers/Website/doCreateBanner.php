<?php
/**
 * Create a Banner
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130806
 * @package MyURY_Website
 */

$data = MyURY_Banner::getBannerForm()->readValues();

$photo = MyURY_Photo::create($data['photo']['tmp_name']);

$banner = MyURY_Banner::create($photo, $data['alt'], $data['target'], $data['type']);

header('Location: '.CoreUtils::makeURL('Website', 'campaigns', [
    'bannerid' => $banner->getBannerID(),
    'message' => base64_encode('Your new Banner has been created!')
        ]));