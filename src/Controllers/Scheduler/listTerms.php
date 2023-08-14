<?php
/**
 *
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Term;

$terms = array_map(
    function ($term) {
	$x = $term->toDataSource();
        $x['start'] = date('d/m/Y', $x['start']);

        return $x;
    },
    MyRadio_Term::getAllTerms()
);

CoreUtils::getTemplateObject()->setTemplate('Scheduler/listTerms.twig')
    ->addVariable('title', 'Scheduler')
    ->addVariable('subtitle', 'Manage Terms')
    ->addVariable('tabledata', CoreUtils::dataSourceParser($terms))
    ->addVariable('tablescript', 'myradio.scheduler.termlist')
    ->render();
