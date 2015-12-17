<?php
/**
 * View an Officer.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Officer;

$officer = MyRadio_Officer::getInstance($_REQUEST['officerid']);

CoreUtils::getTemplateObject()
    ->setTemplate('Profile/officer.twig')
    ->addVariable('title', $officer->getName())
    ->addVariable('officer', $officer->toDataSource(['history', 'permissions']))
    ->render();
