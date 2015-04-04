<?php
/**
 * Creates a NIPSWeb Edit Token for the current session
 *
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\NIPSWeb\NIPSWeb_Token;

$data = ['token' => NIPSWeb_Token::getEditToken()];

echo CoreUtils::dataToJSON($data);
