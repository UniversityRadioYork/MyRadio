<?php
/**
 * Trainin Map
 *
 * @author  Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130624
 * @package MyRadio_Stats
 */

use \MyRadio\MyRadio\CoreUtils;

$options = [
    'title' => 'MyRadio Service Stats',
    'series' => [
        ['targetAxisIndex' => 0],
        ['targetAxisIndex' => 0],
        ['targetAxisIndex' => 1]
    ]
];
CoreUtils::getTemplateObject()->setTemplate('linegraph.twig')
    ->addVariable('title', 'MyRadio Error Rates')
    ->addVariable('data', json_encode(CoreUtils::getErrorStats()))
    ->addVariable('options', json_encode($options))
    ->addVariable(
        'caption',
        'This graph shows information error rate statistics for the last 24 hours. '
        .'Peaks in this graph can suggest a problem.'
    )->render();
