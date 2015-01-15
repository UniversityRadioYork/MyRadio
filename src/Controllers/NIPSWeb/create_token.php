<?php
/**
 * Creates a NIPSWeb Play Token for the Current User and the given trackid
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 17032013
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\MyRadio\URLUtils;
use \MyRadio\NIPSWeb\NIPSWeb_Token;

NIPSWeb_Token::createToken($_REQUEST['trackid']);

URLUtils::nocontent();
