<?php

$data = User::getInstance($_GET['memberid'] || $_SESSION['memberid'])->getTimeline();
require 'Views/MyURY/Profile/timeline.php';
