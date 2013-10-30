<?php
/**
 * CML Digitisation Status
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130803
 * @package MyRadio_Stats
 */
$options = null;
CoreUtils::getTemplateObject()->setTemplate('bargraph.twig')
        ->addVariable('title', 'Central Music Library Content Stats')
        ->addVariable('data', json_encode(MyRadio_Track::getLibraryStats()))
        ->addVariable('options', json_encode($options))
        ->addVariable('caption', 'This graph show aggregate statistics about the contents of The Central Music Library.')
        ->render();