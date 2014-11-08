<?php
/**
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20141026
 * @package MyRadio_Scheduler
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\ServiceAPI;
use \MyRadio\ServiceAPI\MyRadio_Scheduler;

$terms = array_map(function ($x) {
    $x['start'] = date('d/m/Y', $x['start']);
    return $x;
}, MyRadio_Scheduler::getTerms());

CoreUtils::getTemplateObject()->setTemplate('Scheduler/listTerms.twig')
    ->addVariable('title', 'Terms')
    ->addVariable('tabledata', CoreUtils::dataSourceParser($terms))
    ->addVariable('tablescript', 'myradio.scheduler.termlist')
    ->render();
