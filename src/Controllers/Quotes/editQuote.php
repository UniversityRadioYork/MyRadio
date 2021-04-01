<?php
/**
 * Allows the editing of quotes.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Quote;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Quote::getForm()->readValues();

    if (empty($data['id'])) {
        //create new
        MyRadio_Quote::create($data);
    } else {
        //submit edit
        MyRadio_Quote::getInstance($data['id'])
            ->setSource($data['source'])
            ->setText($data['text'])
            ->setDate($data['date']);
    }

    URLUtils::backWithMessage('Content Updated!');
} else {
    //Not Submitted

    if (isset($_REQUEST['quote_id'])) {
        //edit form
        $quote = MyRadio_Quote::getInstance($_REQUEST['quote_id']);

        $quote->getEditForm()->render();
    } else {
        //create form
        MyRadio_Quote::getForm()
            ->setFieldValue('date', CoreUtils::happyTime(time(), false))
            ->render();
    }
}
