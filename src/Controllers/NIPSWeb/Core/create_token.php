<?php
/**
 * Creates a NIPSWeb Play Token for the Current User and the given trackid
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 17032013
 * @package MyURY_NIPSWeb
 */
NIPSWeb_Token::createToken($_REQUEST['trackid']);

require 'Views/MyURY/Core/nocontent.php';