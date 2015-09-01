<?php
/**
 * Lists the archive for a Mailing List
 *
 * @package MyRadio_Mail
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_List;
use \MyRadio\ServiceAPI\MyRadio_User;

$list = MyRadio_List::getInstance($_REQUEST['list']);

if (!$list->isMember(MyRadio_User::getInstance()->getID())) {
    throw new MyRadioException(
        'You can only view archives for Lists you are a'
        .' member of.',
        403
    );
}

$archive = CoreUtils::dataSourceParser($list->getArchive(), false);

foreach ($archive as $key => $value) {
    $archive[$key]['timestamp'] = date('Y/m/d H:i', $archive[$key]['timestamp']);
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.Mail.archive')
    ->addVariable('title', $list->getName().' Archive')
    ->addVariable('tabledata', $archive)
    ->render();
