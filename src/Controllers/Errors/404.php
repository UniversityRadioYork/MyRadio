<?php
/**
 * If you don't know what a 404 page is, you have a long way to go
 *
 * @author  Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\CoreUtils;

$twig = CoreUtils::getTemplateObject();
require 'Views/Errors/404.php';
