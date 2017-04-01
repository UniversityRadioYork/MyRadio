<?php
/**
 * Sets up some config variables for MyRadio.
 */
use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Swagger;

$shorttext = [
    'podcast_archive_path',
    'public_media_path',
    'public_media_uri',
    'music_central_db_path',
    'audio_upload_tmp_dir',
    'lastfm_api_key',
    'reporting_list',
    'error_report_email',
    'log_file',
    'log_file_lock',
    'recaptcha_public_key',
    'recaptcha_private_key',
    'short_name',
    'long_name',
    'founded',
    'email_domain',
    'facebook',
];

$longtext = [
    'welcome_email',
];

/* Paths we might need to create */
$path_cfgs = [
    'webcam_archive_path',
    'podcast_archive_path',
    'public_media_path',
    'audio_upload_tmp_dir',
];

$short_params = [];
$long_params = [];
$rConfig = new ReflectionClass('\MyRadio\Config');

foreach ($shorttext as $key) {
    $rProperty = $rConfig->getProperty($key);
    $name = ucwords(str_replace('_', ' ', $key));
    $desc = implode('<br>', MyRadio_Swagger::parseDoc($rProperty)['lines']);
    $short_params[] = [$key, $name, $desc, Config::$$key];
}

foreach ($longtext as $key) {
    $rProperty = $rConfig->getProperty($key);
    $name = ucwords(str_replace('_', ' ', $key));
    $desc = implode('<br>', MyRadio_Swagger::parseDoc($rProperty)['lines']);
    $long_params[] = [$key, $name, $desc, Config::$$key];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $k => $v) {
        if (Config::$$k !== $v) {
            $config_overrides[$k] = $v;
        }
        if (array_key_exists($k, $path_cfgs) && !is_dir($v) && !mkdir($v, 0755, true)) {
            die("Could not create '$k' directory at '$v'");
        }
    }
    header('Location: ?c=user');
} else {
    CoreUtils::getTemplateObject()
        ->setTemplate('Setup/strings.twig')
        ->addVariable('title', 'Configurables')
        ->addVariable('short', $short_params)
        ->addVariable('long', $long_params)
        ->render();
}
