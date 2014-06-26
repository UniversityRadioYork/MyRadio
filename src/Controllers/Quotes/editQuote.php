<?php
/**
 * Allows the editing of quotes.
 * @version 20131020
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyURY_Quotes
 */

$form = MyRadio_Quote::getForm();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = $form->readValues();

    if (empty($data['id'])) {
        //create new
        MyRadio_Quote::create($data);
    } else {
        //submit edit
        MyRadio_Quote::getInstance($id)
            ->setSource($data['source'])
            ->setText($data['text'])
            ->setDate($data['date']);
    }

    CoreUtils::backWithMessage('Quote Updated.');

} else {
    //Not Submitted

    if (isset($_REQUEST['quote_id'])) {
        //edit form
        $quote = MyRadio_Quote::getInstance($_REQUEST['quote_id']);

        $form->editMode(
            $quote->getID(),
            array_merge(
                [
                    'date'   => CoreUtils::happyTime($quote->getDate(), false),
                    'source' => $quote->getSource(),
                    'text'   => $quote->getText()
                ],
                $chart_rows_form
            )
        );

    } else {
        //create form
        $form->setFieldValue('date', CoreUtils::happyTime(time(), false));
    }

    $form->render();
}
