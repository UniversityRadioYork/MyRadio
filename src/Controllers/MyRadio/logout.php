<?php

/**
 *
 * @author Lloyd Wallis
 * @data 20131230
 * @package MyRadio_Core
 * @todo Throttle quick attempts from one IP with captcha
 * @todo Refactor login code to support Authentication plugins
 */

session_destroy();
header('Location: '.CoreUtils::makeURL('MyRadio', 'login', ['logout' => 1]));
