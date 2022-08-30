<?php

use ReallySimpleJWT\Token;

use \MyRadio\Config;
use \MyRadio\ServiceAPI\MyRadio_User;

// Returns a plaintext page with a JWT for the signed in user
// or 401

if (isset($_SESSION['memberid'])) {
	// would be nice to replace this with an RSA based
	// one, so a consumer can't be a producer

	$payload = [
		'iat' => time(),
		'uid' => $_SESSION['memberid'],
		'name' => MyRadio_User::getCurrentUser()->getName(),
		'exp' => time() + 3600 * 3,
		'iss' => Config::$base_url
	];
	echo Token::customPayload($payload, Config::$jwt_signing_secret);

} else {
	http_response_code(401);
	echo "Unauthorised";
}
