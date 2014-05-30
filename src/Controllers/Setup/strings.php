<?php
/**
 * Sets up some config variables for MyRadio
 *
 * @version 20140515
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 */

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
	'facebook'
];

$longtext = [
	'welcome_email'
];

$short_params = [];
$long_params = [];
$rConfig = new ReflectionClass('Config');

foreach ($shorttext as $key) {
	$rProperty = $rConfig->getProperty($key);
	$name = ucwords(str_replace('_', ' ', $key));
	$desc = implode('<br>', MyRadio_Swagger::parseMethodDoc($rProperty)['lines']);
	$short_params[] = [$key, $name, $desc, Config::$$key];
}

foreach ($longtext as $key) {
	$rProperty = $rConfig->getProperty($key);
	$name = ucwords(str_replace('_', ' ', $key));
	$desc = implode('<br>', MyRadio_Swagger::parseMethodDoc($rProperty)['lines']);
	$long_params[] = [$key, $name, $desc, Config::$$key];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	foreach ($_POST as $k => $v) {
		if (Config::$$k !== $v) {
			$config_overrides[$k] = $v;
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
