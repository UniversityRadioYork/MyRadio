<?php
/**
 * Creates a NIPSWeb Edit Token for the current session
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 16042013
 * @package MyURY_NIPSWeb
 */
$data = NIPSWeb_Token::getEditToken();

require 'Views/MyURY/datatojson.php';