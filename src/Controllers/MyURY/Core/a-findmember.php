<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc.... 
 */
if (!isset($_REQUEST['name'])) throw new MyURYException('Parameter \'name\' is required but was not provided');

$data = User::findByName($_REQUEST['name'], isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : Config::$ajax_limit_default);
require 'Views/MyURY/Core/datatojson.php';