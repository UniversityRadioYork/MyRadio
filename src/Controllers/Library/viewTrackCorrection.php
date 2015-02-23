<?php
/**
 * Allows Librarian-level officers to approve automatically-suggested rec database corrections.
 *
 * @package MyRadio_Library
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_TrackCorrection;

if (isset($_REQUEST['correctionid']) && is_numeric($_REQUEST['correctionid'])) {
    $correction = MyRadio_TrackCorrection::getInstance($_REQUEST['correctionid']);
} else {
    $correction = MyRadio_TrackCorrection::getRandom();
}

if (empty($correction)) {
    CoreUtils::getTemplateObject()->setTemplate('MyRadio/text.twig')
        ->addVariable('title', 'Central Database Metadata Correction Proposal Review')
        ->addVariable('text', 'There are no proposals to review right now.')
        ->render();
} else {
    CoreUtils::getTemplateObject()->setTemplate('Library/viewTrackCorrection.twig')
        ->addVariable('title', 'Central Database Metadata Correction Proposal Review')
        ->addVariable('correction', $correction->toDataSource())
        ->render();
}
