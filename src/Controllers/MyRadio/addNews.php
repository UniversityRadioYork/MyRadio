<?php

/**
 * This is the controller for the news items
 * members news, tech news and the presenter information sheet
 *
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\MyRadioNews;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadioNews::getForm()->readValues();

    MyRadioNews::addItem($container['database'], $data['feedid'], $data['body']);

    CoreUtils::backWithMessage('News Updated!');

} else {
    //Not Submitted
    MyRadioNews::getForm()
        ->setFieldValue('feedid', $_REQUEST['feed'])
        ->render();
}
