<?php
/**
 * Landing page for iTones
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyRadio_iTones
 */

use \MyRadio\MyRadio\CoreUtils;

CoreUtils::getTemplateObject()
    ->setTemplate('iTones/default.twig')
    ->addVariable('title', 'Campus Jukebox Manager')
    ->render();
