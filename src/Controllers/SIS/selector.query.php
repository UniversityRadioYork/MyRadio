<?php
$sel = new MyURY_Selector();

$status = $sel->query();
$onair = (int) $status[0];
$locked = (int) $status[1];
$power = (int) $status[3];
$s1power = ($power & 1);
$s2power = ($power & 2) >> 1;
print $status;
print "$lastmod $s1power $s2power $onair $locked";
return;
