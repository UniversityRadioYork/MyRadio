<?php
/**
 * Trainin Map
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130624
 * @package MyURY_Stats
 */
CoreUtils::getTemplateObject()->setTemplate('linegraph.twig')
        ->addVariable('title', 'MyURY Error Rates')
        ->addVariable('data', json_encode(CoreUtils::getErrorStats()))
        ->render();