<?php
$sel = new MyURY_Selector();

$status = $sel->query();

$power = $status['power'];
$s1power = ($power & 1);
$s2power = ($power & 2) >> 1;

print "$lastmod $s1power $s2power $status['studio'] $status['lock']";
return;
