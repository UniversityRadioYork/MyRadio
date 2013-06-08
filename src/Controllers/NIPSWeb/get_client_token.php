<?php
/**
 * Creates a NIPSWeb Edit Token for the current session
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130608
 * @package MyURY_NIPSWeb
 */
$data = array('token' => NIPSWeb_Token::getEditToken());

require 'Views/MyURY/datatojson.php';