<?php
/**
 * Allows URY Librarians to search for Tracks.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;
use \MyRadio\MyRadio\URLUtils;
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
            new MyRadioFormField('title', MyRadioFormField::TYPE_TEXT, ['required' => false, 'label' => 'Title'])
        )->addField(
            new MyRadioFormField('artist', MyRadioFormField::TYPE_TEXT, ['required' => false, 'label' => 'Artist'])
        );
        
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Track::getSearchForm()->readValues();

    if (isset($data['title']) || isset($data['title'])) {
        $tracks = MyRadio_Track::findByOptions(
            [
                    'title' => isset($data['title']) ? $data['title'] : '',
                    'artist' => isset($data['title']) ? $data['title'] : ''
            ]
        );
    } else {
        $tracks = null;
    }

}

$form->setTemplate('Library/search.twig')
    ->render([
        'tabledata' => CoreUtils::dataSourceParser($tracks),
        'tablescript' => 'myradio.library.search',
        ]
        );
    

