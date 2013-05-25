<?php
require 'Views/bootstrap.php';

if ($member->hasAuth(AUTH_ALLOCATESLOTS) && isset($to_allocate) && $to_allocate !== 0) {
  $twig->addInfo('There are '.$to_allocate.' show applications to be processed', 'clock');
}
if ($member->hasAuth(AUTH_ALLOCATESLOTS) && isset($disputes) && $disputes !== 0) {
  $twig->addInfo('There are '.$disputes.' processed applications that have been disputed', 'comment');
}