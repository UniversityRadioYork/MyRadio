<?php
/**
 * Returns the APC upload progress data for the given upload ID
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130816
 * @package MyURY_Core
 */

$data = apc_fetch('upload_'.$_REQUEST['id']);

require 'Views/MyURY/datatojson.php';