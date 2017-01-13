<?php

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;

// $tracks = MyRadio_Track::getAllDigitised();
if (isset($_REQUEST['title']) || isset($_REQUEST['artist'])) {
    $tracks = MyRadio_Track::findByOptions(
        [
                'title' => isset($_REQUEST['title']) ? $_REQUEST['title'] : '',
                'artist' => isset($_REQUEST['artist']) ? $_REQUEST['artist'] : ''
        ]
    );
}

CoreUtils::getTemplateObject()->addError('Currently, the exact Track and/or Artist must be entered.')
    ->setTemplate('table.twig')
    ->addVariable('tablescript', 'myury.library.search')
    ->addVariable('tabledata', CoreUtils::dataSourceParser($tracks))
    ->addVariable('title', 'Search Library')
    ->addVariable(
        'text',
        'Here you can search for tracks in the Central Music Library. <form action="#" method="get" class="form-inline"><input type="text" name="title" size="30" class="form-control" placeholder="Search by Track" /><input type="text" name="artist" size="30" class="form-control" placeholder="Search by Artist" /><button class="btn btn-primary" type="submit">Submit</button></form>'
    )
    ->render();
?>