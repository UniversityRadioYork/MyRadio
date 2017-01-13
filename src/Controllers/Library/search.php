<?php
/**
 * Allows URY Librarians to search for Tracks.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;
use \MyRadio\MyRadio\MyRadioForm;
use \MyRadio\MyRadio\MyRadioFormField;

$form = new MyRadioForm(
    'lib_search',
    'Library',
    'search',
    [
        'title' => 'Library Search',
    ]
);

$form->addField(
    new MyRadioFormField('title', MyRadioFormField::TYPE_TEXT, ['required' => false, 'label' => 'Title', 'placeholder' => 'Filter by track title...'])
)->addField(
    new MyRadioFormField('artist', MyRadioFormField::TYPE_TEXT, ['required' => false, 'label' => 'Artist', 'placeholder' => 'Filter by artist name...'])
);
        
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = $form->readValues();

    if (isset($data['title']) || isset($data['title'])) {
        $tracks = MyRadio_Track::findByOptions(
            [
                    'title' => isset($data['title']) ? $data['title'] : '',
                    'artist' => isset($data['artist']) ? $data['artist'] : '',
                    'limit' => 30
            ]
        );
    } else {
        $tracks = null;
    }

}
$tableData = CoreUtils::dataSourceParser($tracks);

$form->setTemplate('Library/search.twig')
    ->render([
        'tabledata' => $tableData,
        'tablescript' => 'myradio.library.search',
        ]
        );
    

