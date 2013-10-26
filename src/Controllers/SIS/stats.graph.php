<?php
error_reporting(0);
$fname = (isset($_REQUEST['f'])) ? $_REQUEST['f'] : 'https://ury.org.uk/sis2/streamstats-ury.txt';
$f = file($fname);

$max = 0;
foreach ($f as &$l) {
  $l = explode(' ', $l);
  if ((int) $l[1] > $max)
    $max = (int) $l[1];
}

$barwidth = 3.4;
$imagewidth = 250;
$imageheight = 100;
$padding = 10;
$maxheight = $imageheight - $padding * 3;

$left = $imagewidth - ($barwidth * (count($f) - 1)) - 2 * $padding;
//print $left;

$image = imagecreatetruecolor($imagewidth, $imageheight);

// Turn off alpha blending and set alpha flag
//imagealphablending($image, false);
//imagesavealpha($image, true);

$black = imagecolorallocate($image, 0, 0, 0);
imagecolortransparent($image, $black);

$coltext = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
$coledge = imagecolorallocate($image, 0xDD, 0xDD, 0xDD);
$colfill = imagecolorallocate($image, 0x36, 0x3D, 0x5F);
$colstrm = imagecolorallocate($image, 0xFF, 0x99, 0x33);

imageline($image, $left, $padding, $left, $imageheight - 2 * $padding, $coledge);
imagestring($image, 4, 5, $padding - 5, $max, $coltext);
imagestring($image, 4, 5, $imageheight - 2 * $padding - 5, '0', $coltext);
imagestring($image, 2, $left, $padding + $maxheight + 2, date("H:i", (int) $f[0][0]), $coltext);
imagestring($image, 2, $imagewidth - 2 * $padding - 30, $padding + $maxheight + 2, date("H:i", (int) $f[count($f) - 1][0]), $coltext);

$points = array();
$points[] = $left;
$points[] = $maxheight + $padding;
$maxleft = NULL;
$maxdate = NULL;

foreach ($f as $p) {
  $points[] = $left;
  $points[] = (($maxheight * ($max - (int) $p[1])) / $max) + $padding;
  if ((int) $p[1] == $max) {
    $maxleft = $left;
    $maxdate = $p[0];
  }
  $left += $barwidth;
}
$points[] = $imagewidth - 2 * $padding;
$points[] = $maxheight + $padding;

imagefilledpolygon($image, $points, count($points) / 2, $colfill);
imagepolygon($image, $points, count($points) / 2, $coledge);
imagestring($image, 2, $maxleft - 14, $maxheight - 5, date("H:i", (int) $maxdate) . " ($max)", $coltext);
imageline($image, $maxleft, $padding, $maxleft, $maxheight + $padding, $coledge);
imagestring($image, 2, $imagewidth - 2 * $padding + 4, (($maxheight * ($max - (int) $f[count($f) - 1][1])) / $max) - 5 + $padding, $f[count($f) - 1][1], $coltext);

imagestring($image, 2, $padding*2+40, $maxheight+$padding, $fname, $colstrm);

header('Content-type: image/png');
imagepng($image);
imagedestroy($image);


