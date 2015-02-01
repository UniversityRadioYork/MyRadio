<?php
/**
 * The most messaged shows this academic year
 *
 * @author  Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130626
 * @package MyRadio_Stats
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Show;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Most messaged shows this academic year')
    ->addVariable('tabledata', MyRadio_Show::getMostMessaged(strtotime(CoreUtils::getAcademicYear().'-09-01')))
    ->addVariable('tablescript', 'myury.datatable.default')
    ->render();
