<?php
/**
 * For versions other than the default, static content is not linked directly to the web.
 * This provides access to these, at the cost of a substantial overhead.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyURY_Core
 */

if (empty($_GET[0])) {
  require 'default.php';
  exit;
}

//For config.js, this is a Controller in this module.
if ($_GET[0] === 'config.js') {
  require __DIR__.'/config.js.php';
  exit;
}

$prefix = __DIR__.'/../../Public/';

if (strstr($_GET[0], '..') !== false) exit;

header('Content-Type: '.mime_content_type($prefix.$_GET[0]));
echo file_get_contents($prefix.$_GET[0]);