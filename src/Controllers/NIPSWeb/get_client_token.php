<?php
/**
 * Creates a NIPSWeb Edit Token for the current session
 *
 * @author  Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130608
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\NIPSWeb\NIPSWeb_Token;

$data = ['token' => NIPSWeb_Token::getEditToken()];

CoreUtils::dataToJSON($data);
