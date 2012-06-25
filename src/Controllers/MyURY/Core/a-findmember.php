<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc.... 
 */
$data = User::findByName($_REQUEST['name'], isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : Config::$ajax_limit_default);
require 'Views/MyURY/Core/datatojson.php';