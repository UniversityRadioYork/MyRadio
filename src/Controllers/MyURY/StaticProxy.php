<?php
/**
 * For versions other than the default, static content is not linked directly to the web.
 * This provides access to these, at the cost of a substantial overhead.
 */

$explode = array_reverse(explode('/', $_SERVER['REQUEST_URI']));

//For config.js, this is a Controller in this module.
if ($explode[0] === 'config.js') require __DIR__.'/config.js.php'; exit;

$path_prefix = '';

for ($i = 1; $i < sizeof($explode); $i++) {
  if ($explode[$i] === '.' or $explode[$i] === '..') exit; //DIRECTORY TRAVERSAL!
  
  if ($explode[$i] === 'img' or $explode[$i] === 'js') {
    //We know where we are
    require __DIR__.'/../Public'.$path_prefix.'/'.$explode[0];
    exit;
  }
  
  $path_prefix .= '/'.$explode[$i];
}