<?php
/**
 * Trainin Map
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130624
 * @package MyURY_Stats
 */
$options = array(
    'title' => 'MyURY Service Stats',
    'series' => array(
        array('targetAxisIndex' => 0),
        array('targetAxisIndex' => 0),
        array('targetAxisIndex' => 1)
    ),
    'curveType' => 'function'
);
CoreUtils::getTemplateObject()->setTemplate('linegraph.twig')
        ->addVariable('title', 'MyURY Error Rates')
        ->addVariable('data', json_encode(CoreUtils::getErrorStats()))
        ->addVariable('options', json_encode($options))
        ->addVariable('caption', 'This graph shows information error rate statistics for the last 24 hours.
          Peaks in this graph can suggest a problem.')
        ->render();