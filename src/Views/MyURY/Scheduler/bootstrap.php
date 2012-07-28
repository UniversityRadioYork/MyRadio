<?php
require 'Views/MyURY/bootstrap.php';

if ($to_allocate !== 0) $twig->addInfo('There are '.$to_allocate.' show applications to be processed', 'clock');
if ($disputes !== 0) $twig->addInfo('There are '.$disputes.' processed applications that have been disputed', 'comment');