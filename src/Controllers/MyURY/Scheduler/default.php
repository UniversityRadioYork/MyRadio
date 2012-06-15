<?php
CoreUtils::requirePermission(AUTH_ALLOCATESLOTS);

require 'Models/MyURY/Scheduler/notices.php';
require 'Models/MyURY/Scheduler/pendingAllocations.php';
require 'Views/MyURY/Scheduler/default.php';