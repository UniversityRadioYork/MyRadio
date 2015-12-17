<?php
/**
 * Creates a NIPSWeb Edit Token for the current session.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\NIPSWeb\NIPSWeb_Token;

$data = ['token' => NIPSWeb_Token::getEditToken()];

URLUtils::dataToJSON($data);
