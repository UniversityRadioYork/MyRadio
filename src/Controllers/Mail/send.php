<?php
/**
 * Send an email to a mailing list
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130526
 * @package MyURY_Mail
 */

//The Form definition
require 'Models/Mail/sendfrm.php';
$form->setFieldValue('list', $_REQUEST['list'])
                ->render();