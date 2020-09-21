<?php

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\ServiceAPI\MyRadio_Event;

if(empty($_REQUEST['start']) || empty($_REQUEST['end'])) {
    URLUtils::dataToJSON([
        'myradio_errors' => [
            'missing start or end'
        ]
    ]);
    exit;
}

$data = CoreUtils::setToDataSource(MyRadio_Event::getInRange(
    $_REQUEST['start'],
    $_REQUEST['end']
));

URLUtils::dataToJSON($data);
