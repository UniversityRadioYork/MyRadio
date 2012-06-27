<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc.... 
 */
if (!isset($_REQUEST['term'])) throw new MyURYException('Parameter \'term\' is required but was not provided');

$data = Artist::findByName($_REQUEST['term'], isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : Config::$ajax_limit_default);
require 'Views/MyURY/Core/datatojson.php';