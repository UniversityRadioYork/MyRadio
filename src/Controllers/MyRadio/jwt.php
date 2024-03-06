<?php

use ReallySimpleJWT\Token;

use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\ServiceAPI\MyRadio_User;

if (isset($_GET['redirectto'])) {
	// would be nice to replace this with an RSA based
	// one, so a consumer can't be a producer

	$payload = [
		'iat' => time(),
		'uid' => $_SESSION['memberid'],
		'name' => MyRadio_User::getCurrentUser()->getName(),
		'exp' => time() + 3600 * 3,
		'iss' => Config::$base_url
	];
	
	header("Location: " . $_GET["redirectto"] . "?jwt=" . Token::customPayload($payload, Config::$jwt_signing_secret));

} else {
	throw new MyRadioException('redirectto must be provided', 400);
}
