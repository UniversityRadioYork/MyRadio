<?php
/**
 * Edit a Banner
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130806
 * @package MyURY_Website
 */

$data = MyURY_Banner::getBannerForm()->readValues();

$banner = MyURY_Banner::getInstance($data['id'])
        ->setAlt($data['alt'])
        ->setTarget($data['target'])
        ->setType($data['type']);

if ($data['photo']['error'] == 0) {
  //Upload replacement Photo
  $banner->setPhoto(MyURYPhoto::create($data['photo']['tmp_name']));
}

header('Location: '.CoreUtils::makeURL('Website', 'banners', [
    'message' => base64_encode('The Banner was updated successfully!')
]));