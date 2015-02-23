<?php
/**
 * For versions other than the default, static content is not linked directly to the web.
 * This provides access to these, at the cost of a substantial overhead.
 *
 * @author  Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyRadio_Core
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
foreach ([__DIR__.'/../../Public/', __DIR__.'/../../Public/js/vendor/skins/lightgray/'] as $p) {
    if (file_exists($p.$_GET[0])) {
        $prefix = $p;
        break;
    }
}

if (strstr($_GET[0], '..') !== false) {
    exit;
}

if (strtolower(substr($_GET[0], -3)) == 'css') {
    $type = 'text/css';
} elseif (strtolower(substr($_GET[0], -2)) == 'js') {
    $type = 'text/javascript';
} else {
    $type = mime_content_type($prefix.$_GET[0]);
}

header('Content-Type: '.$type);
echo file_get_contents($prefix.$_GET[0]);
