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

$terms = MyRadio_Scheduler::getTerms();
CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Terms')
    ->addVariable('tabledata', ServiceAPI::setToDataSource($terms))
    ->addVariable('tablescript', 'myury.scheduler.termlist')
    ->render();
