<?php
/**
 * Stats Overview
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130624
 * @package MyRadio_Stats
 */
CoreUtils::getTemplateObject()->setTemplate('MyRadio/text.twig')
        ->addVariable('title', 'Statistics')
        ->addVariable('text', 'This part of MyRadio shows you some interesting statistics about the station, from training maps to college breakdowns.')
        ->render();
