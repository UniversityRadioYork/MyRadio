<?php

/**
 *
 * This is the controller for the news items
 * members news, tech news and the presenter information sheet
 *
 * @author Lloyd Wallis
 * @data 20131228
 * @package MyRadio_Core
 */
$form = (
    new MyRadioForm(
        'myradio_news',
        'MyRadio',
        'addNews',
        array(
            'title' => 'Add news item'
        )
    )
)->addField(
    new MyRadioFormField(
        'body',
        MyRadioFormField::TYPE_BLOCKTEXT,
        array(
            'explanation' => '',
            'label' => 'Content'
        )
    )
)->addField(
    new MyRadioFormField(
        'feedid',
        MyRadioFormField::TYPE_HIDDEN
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = $form->readValues();
    MyRadioNews::addItem($data['feedid'], $data['body']);
    CoreUtils::redirect('MyRadio', 'news', ['feed' =>$data['feedid']]);
} else {
    //Not Submitted
    $form->setFieldValue('feedid', $_REQUEST['feed'])->render();
}
