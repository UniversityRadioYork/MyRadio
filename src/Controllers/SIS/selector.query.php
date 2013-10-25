<?php
$lastmoduser = (isset($_REQUEST['lastchange'])) ? (int) $_REQUEST['lastchange'] : 0;
$lastmod = filemtime($selectorStatusFile);
if ($lastmod <= $lastmoduser) {
  print "not modified";
  return;
}

$sel = new MyURY_Selector();

$status = $sel->query();
$onair = (int) $status[0][0];
$locked = (int) $status[0][1];
$power = (int) $status[0][3];
$s1power = ($power & 1);
$s2power = ($power & 2) >> 1;
print "$lastmod $s1power $s2power $onair $locked";
return;
