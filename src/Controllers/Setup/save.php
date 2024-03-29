<?php
/**
 * Saves the Setup data to MyRadio_Config.local.php.
 */
use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;

$path = realpath(__DIR__ . '/../../') . '/MyRadio_Config.local.php';

//Merge existing config
if (file_exists($path)) {
    $old_config = file($path);
    require $path; //Reload so that Config:: has the right values
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
// We might be behind a reverse proxy
if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $domain = $_SERVER['HTTP_X_FORWARDED_HOST'];
} else if (!empty($_SERVER['HTTP_HOST'])) {
    $domain = $_SERVER['HTTP_HOST'];
} else {
    $domain = $_SERVER['SERVER_NAME'];
}
$config_overrides['base_url'] = '//'.$domain.explode('?', $_SERVER['REQUEST_URI'])[0];

// Build the config file
$file_str = "<?php\nuse \\MyRadio\\Config;\n";
foreach ($config_overrides as $k => $v) {
    if (is_numeric($v) != true && is_bool($v) != true) {
        $v = "'".str_replace("'", "\\'", $v)."'";
    } elseif ($v === true) {
        $v = 'true';
    } elseif ($v === false) {
        $v = 'false';
    }
    $file_str .= 'Config::$'.$k.' = '.strval($v).";\n";
}

//Actually write the file
$file = @fopen($path, 'w');
if (!$file) {
    //...or not
    CoreUtils::getTemplateObject()
        ->setTemplate('minimal.twig')
        ->addVariable(
            'content',
            "An error occurred saving your settings.\n"
            ."This is OK! It's probably best that the web server doesn't have write access to the webroot.\n"
            ."Either write out the following into '$path' or give me write access to it, then reload this page.\n\n"
        )
        ->addVariable(
            'rawcontent',
            "<button data-toggle='collapse' data-target='#config'>Show config</button><br><br>"
            ."<textarea id='config' class='form-control collapse' readonly rows=20>"
            .$file_str
            .'</textarea>'
        )
        ->render();
} else {
    fwrite($file, $file_str);
    fclose($file);
    header('Location: ./');
}
