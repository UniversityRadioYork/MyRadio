<?php
$sel = new MyURY_Selector();

$status = $sel->query();
$onair = $status['studio'];
$locked = $status['lock'];
$power = $status['power'];
$s1power = ($power & 1);
$s2power = ($power & 2) >> 1;
$lastmod = 0;
print "$lastmod $s1power $s2power $onair $locked";
return;
