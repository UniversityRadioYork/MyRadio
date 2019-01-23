<?php
/**
 * Lists all mailing lists.
 *
 * @todo    Datatable niceness
 */
use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_List;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.mail.default')
    ->addVariable('title', 'All Mailing Lists')
    ->addVariable('tabledata', CoreUtils::dataSourceParser(MyRadio_List::getUserLists(), ['actions']))
    ->addInfo(
        'You will only get any messages from '
        .Config::$short_name
        .' if you are set to "Receive Email" on your <a href="'
        .URLUtils::makeURL('Profile', 'edit')
        .'">profile</a>.'
    )->render();
