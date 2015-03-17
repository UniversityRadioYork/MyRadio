<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc....
 *
 * @todo    Proper documentation
 * @package MyRadio_Scheduler
 */

use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Scheduler;

if (!isset($_REQUEST['term'])) {
    throw new MyRadioException('Parameter \'term\' is required but was not provided');
}

$data = MyRadio_Scheduler::findShowByTitle(
    $_REQUEST['term'],
    isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : $container['config']->ajax_limit_default
);
CoreUtils::dataToJSON($data);
