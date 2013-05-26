<?php
/**
 * Lists all mailing lists
 * 
 * @todo Datatable niceness
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130526
 * @package MyURY_Mail
 */

$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.datatable.default')
        ->addVariable('title', 'All Mailing Lists')
        ->addVariable('tabledata', CoreUtils::dataSourceParser(MyURY_List::getAllLists()))
        ->render();
