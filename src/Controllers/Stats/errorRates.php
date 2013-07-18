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
        ->addVariable('caption', 'This graph shows information error rate statistics for the last 24 hours.
          Peaks in this graph can suggest a problem.')
        ->render();