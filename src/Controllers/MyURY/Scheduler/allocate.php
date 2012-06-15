<?php
CoreUtils::requirePermission(AUTH_ALLOCATESLOTS);

require 'Models/MyURY/Scheduler/notices.php';
require 'Models/MyURY/Scheduler/entry.php';
require 'Views/MyURY/Scheduler/allocate.php';