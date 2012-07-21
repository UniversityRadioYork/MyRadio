<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc.... 
 * 
 * @todo proper documentation
 * @todo Support for predefined artist and/or record as per
 * https://ury.york.ac.uk/members/wiki/computing:software:in-house:myury?&#core_js_api
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 */
if (!isset($_REQUEST['term'])) throw new MyURYException('Parameter \'term\' is required but was not provided');

$data = Track::findByName($_REQUEST['term'], isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : Config::$ajax_limit_default);
require 'Views/MyURY/Core/datatojson.php';