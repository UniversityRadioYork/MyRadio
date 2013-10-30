<?php
/**
 * Lists the archive for a Mailing List
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130828
 * @package MyRadio_Mail
 */

$list = MyRadio_List::getInstance($_REQUEST['list']);

if (!$list->isMember(User::getInstance())) {
  throw new MyRadioException('You can only view archives for Lists you are a'
          . ' member of.', 403);
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.datatable.default')
        ->addVariable('title', $list->getName().' Archive')
        ->addVariable('tabledata', CoreUtils::dataSourceParser($list->getArchive(), false))
        ->render();