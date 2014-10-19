<?php
/**
 * Main renderer for Timelord
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130904
 * @package MyRadio_Timelord
 */

use \MyRadio\MyRadio\CoreUtils;

CoreUtils::getTemplateObject()->setTemplate('Timelord/main.twig')
    ->addVariable('title', 'Studio Clock')
    ->render();
