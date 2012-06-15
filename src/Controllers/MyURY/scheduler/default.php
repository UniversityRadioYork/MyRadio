<?php
CoreUtils::requirePermission(AUTH_ALLOCATESHOWS);
print_r($_SESSION);

require 'Models/MyURY/Scheduler/notices.php';
require 'Models/MyURY/Scheduler/pendingAllocations.php';
require 'Views/MyURY/Scheduler/default.php';