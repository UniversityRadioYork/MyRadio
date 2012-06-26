<?php

$data = User::getInstance(isset($_GET['memberid']) ? $_GET['memberid'] : $_SESSION['memberid'])->getTimeline();
require 'Views/MyURY/Profile/timeline.php';
