<?php
/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyRadio_Scheduler
 */

$shows = MyRadio_Show::getAllShows();
CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'All Shows')
    ->addVariable('tabledata', ServiceAPI::setToDataSource($shows))
    ->addVariable('tablescript', 'myury.scheduler.showlist')
    ->render();
