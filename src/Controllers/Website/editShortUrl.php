<?php
/**
 * Edit a Banner.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Photo;
use MyRadio\ServiceAPI\MyRadio_ShortURL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_ShortURL::getForm()->readValues();

    if (empty($data['id'])) {
        //create new
        $shortUrl = MyRadio_ShortURL::create($data['slug'], $data['redirect_to']);
    } else {
        //submit edit
        $shortUrl = MyRadio_ShortURL::getInstance($data['id'])
            ->setSlug($data['slug'])
            ->setRedirectTo($data['redirec_to']);
    }

    URLUtils::backWithMessage('Short URL updated! ' .
        'Please note that it can take up to 10 minutes for it to become active.');
} else {
    //Not Submitted

    if (isset($_REQUEST['shorturlid'])) {
        //edit form
        /** @var MyRadio_ShortURL $shortUrl */
        $shortUrl = MyRadio_ShortURL::getInstance($_REQUEST['shorturlid']);
        $shortUrl
            ->getEditForm()
            ->render();
    } else {
        //create form
        MyRadio_ShortURL::getForm()->render();
    }
}
