<?php

$data = (new User($_GET['memberid'] || $_SESSION['memberid']))->getTimeline();
require 'Views/MyURY/Profile/timeline.php';
