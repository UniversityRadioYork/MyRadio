<?php

/**
 * This is the controller for the news items
 * members news, tech news and the presenter information sheet.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\MyRadio\MyRadioNews;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadioNews::getForm()->readValues();

    MyRadioNews::addItem($data['feedid'], $data['body']);

    URLUtils::backWithMessage('News Updated!');
} else {
    //Not Submitted
    MyRadioNews::getForm()
        ->setFieldValue('feedid', $_REQUEST['feed'])
        ->setFieldValue('body', MyRadioNews::getLatestNewsItem($_REQUEST['feed'])['content'])
        ->render();
}
