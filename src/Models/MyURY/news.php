<?php

if (isset($_GET['feed'])) {
  $newsFeed = $_GET['feed'];
}
else {
  require 'Controllers/Errors/404.php';
  // @todo use the currently unwritten error handler
}

