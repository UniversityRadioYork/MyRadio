<?php

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;



/**
 * Allows URY Librarians to search for Tracks.
 */
use \MyRadio\MyRadio\URLUtils;
//use \MyRadio\ServiceAPI\MyRadio_Track;

            $form = new MyRadioForm(
                'lib_search',
                'Library',
                'search',
                [
                    'title' => 'Library Search'
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

    // $tracks = MyRadio_Track::getAllDigitised();
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

    //CoreUtils::getTemplateObject()->setTemplate('table.twig')
      //  ->addVariable('tablescript', 'myury.library.search')
      //  ->addVariable('tabledata', CoreUtils::dataSourceParser($tracks))
      //  ->addVariable(
      //      'text',
      //      'Here you can search for tracks in the Central Music Library.'
      //  )
      //  ->render();

    //$track = MyRadio_Track::getInstance($data['id']);
    //$track->setTitle($data['title']);
    //$track->setArtist($data['artist']);
    //$track->setAlbum($data['album']);

    //URLUtils::backWithMessage('Track Updated.');
} //else {
    //Not Submitted
        $form->setTemplate('Library/search.twig')
            ->render([
            'tabledata' => CoreUtils::dataSourceParser($tracks),
            'tablescript' => 'myury.library.search'
            ]
        );
    
//}
