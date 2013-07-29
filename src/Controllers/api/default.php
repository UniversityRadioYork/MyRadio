<?php
require_once 'Classes/Vendor/Restler/vendor/restler.php';
use Luracast\Restler\Restler;

$r = new Restler();
$r->addAPIClass('CoreUtils'); // repeat for more
$r->handle(); //serve the response