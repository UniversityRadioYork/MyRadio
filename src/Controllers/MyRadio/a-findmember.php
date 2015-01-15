<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc....
 *
 * @todo Proper documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyRadio_Core
 */

use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

if (!isset($_REQUEST['term'])) {
    throw new MyRadioException('Parameter \'term\' is required but was not provided');
}

$data = MyRadio_User::findByName(
    $_REQUEST['term'],
    isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : Config::$ajax_limit_default
);

URLUtils::dataToJSON($data);
