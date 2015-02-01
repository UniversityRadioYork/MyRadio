<?php
/**
 * Saves the Setup data to MyRadio_Config.local.php
 *
 * @package MyRadio_Core
 */
use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;

$path = __DIR__ . '/../../MyRadio_Config.local.php';

//Merge existing config
if (file_exists($path)) {
    $old_config = file($path);
    include $path; //Reload so that Config:: has the right values
} else {
    $old_config = [];
}

foreach ($old_config as $line) {
    $count = 0;
    $param = preg_replace('/[ \t]*Config::\$([^ ]*) .*/', '$1', $line, 1, $count);
    if ($count == 0) {
        //This isn't a variable...
        continue;
    }
    if (!isset($config_overrides[$param]) && property_exists('Config', $param)) {
        $config_overrides[$param] = Config::$$param;
    }
}

//Work out URLs
if (!empty($_SERVER['HTTP_HOST'])) {
    $domain = $_SERVER['HTTP_HOST'];
} else {
    $domain = $_SERVER['SERVER_NAME'];
}
$config_overrides['base_url'] = '//' . $domain . explode('?', $_SERVER['REQUEST_URI'])[0];

//Actually write the file
$file = @fopen($path, 'w');
if (!$file) {
    //...or not
    CoreUtils::getTemplateObject()
        ->setTemplate('minimal.twig')
        ->addVariable('content', 'An error occurred saving your settings. Please make sure I have write access to ' . $path . ' and then reload this page.')
        ->render();
        exit;
}
fwrite($file, "<?php\n");
fwrite($file, "use \\MyRadio\\Config;\n");
foreach ($config_overrides as $k => $v) {
    if (is_numeric($v) != true && is_bool($v) != true) {
        $v = "'" . str_replace("'", "\\'", $v) . "'";
    } elseif ($v === true) {
        $v = 'true';
    } elseif ($v === false) {
        $v = 'false';
    }
    fwrite($file, 'Config::$' . $k . ' = ' . strval($v) . ";\n");
}

fclose($file);
header('Location: ./');
