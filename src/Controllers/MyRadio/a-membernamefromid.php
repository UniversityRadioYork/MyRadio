<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc....
 *
 * @todo Proper documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 02082012
 * @package MyRadio_Core
 */
if (!isset($_REQUEST['term'])) {
    throw new MyRadioException('Parameter \'term\' is required but was not provided');
}

$data = MyRadio_User::getInstance((int) $_REQUEST['term'])->getName();
require 'Views/MyRadio/datatojson.php';
