<?php
/**
 * Selector setter for SIS.
 *
 * @todo    Lots of duplication with MyRadio_Selector here
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Selector;

$src = (isset($_REQUEST['src'])) ? (int) $_REQUEST['src'] : 0;
$status = MyRadio_Selector::getStatusAtTime();

if (($src <= 0) || ($src > 8)) {
    $data = ['error' => 'Invalid Selection'];
} elseif ($src == $status['studio']) {
    $data = ['error' => 'Source '.$src.' already selected'];
} elseif ((($src == 1) && (!$status['s1power']))
    || (($src == 2) && (!$status['s2power']))
    || (($src == 4) && (!$status['s4power']))
) {
    $data = ['error' => 'Source '.$src.' is not powered.'];
} elseif ($status['lock'] != 0) {
    $data = ['error' => 'locked'];
} else {
    $response = MyRadio_Selector::setStudio($src);

    if (!empty($response)) {
        $data = $response;
    } else {
        $data = MyRadio_Selector::getStatusAtTime();
    }
}

URLUtils::dataToJSON($data);
