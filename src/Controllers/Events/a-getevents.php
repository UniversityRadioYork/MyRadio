<?php

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\ServiceAPI\MyRadio_Event;

$data = CoreUtils::setToDataSource(MyRadio_Event::getInRange(
    $_REQUEST['start'],
    $_REQUEST['end']
));

URLUtils::dataToJSON($data);
