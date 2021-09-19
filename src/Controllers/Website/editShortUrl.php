<?php
/**
 * Edit a Banner.
 */

use MyRadio\Config;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Photo;
use MyRadio\ServiceAPI\MyRadio_ShortURL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_ShortURL::getForm()->readValues();

    $slug = trim($data['slug']);

    if ($slug[0] === '/') {
        URLUtils::backWithMessage("Slugs can't start with a slash.");
        exit;
    }

    foreach (Config::$short_url_forbidden_slugs as $test) {
        if (strpos($slug, $test) === 0) {
            URLUtils::backWithMessage("You can't use '$test' as a slug. Sorry. Please choose another one.");
            exit;
        }
    }

    if (empty($data['id'])) {
        //create new
        $shortUrl = MyRadio_ShortURL::create($slug, $data['redirect_to']);
    } else {
        //submit edit
        $shortUrl = MyRadio_ShortURL::getInstance($data['id'])
            ->setSlug($slug)
            ->setRedirectTo($data['redirect_to']);
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
