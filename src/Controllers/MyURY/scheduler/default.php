<?php

CoreUtils::requirePermission(AUTH_ALLOCATESHOWS);

require 'Models/MyURY/Scheduler/notices.php';
require 'Models/MyURY/Scheduler/pendingAllocations.php';
require 'Views/MyURY/Scheduler/default.php';