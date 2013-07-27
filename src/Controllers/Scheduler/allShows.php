<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */

$shows = MyURY_Show::getAllShows();
$twig = CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('title', 'All Shows')
        ->addVariable('tabledata', ServiceAPI::setToDataSource($shows))
        ->addVariable('tablescript', 'myury.scheduler.showlist');

$twig->render();