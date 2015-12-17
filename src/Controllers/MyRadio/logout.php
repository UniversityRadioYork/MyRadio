<?php

/**
 * @data 20131230
 *
 * @todo Throttle quick attempts from one IP with captcha
 * @todo Refactor login code to support Authentication plugins
 */
use \MyRadio\MyRadio\URLUtils;

session_destroy();
URLUtils::redirect('MyRadio', 'login', ['logout' => 1]);
