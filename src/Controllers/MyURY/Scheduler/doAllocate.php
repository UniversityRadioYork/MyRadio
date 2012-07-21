<?php
CoreUtils::requirePermission(AUTH_ALLOCATESLOTS);

//The Form definition
require 'Models/MyURY/Scheduler/allocatefrm.php';
print_r($form->readValues());

throw new MyURYException('Not Implemented', MyURYException::FATAL);