<?php
/**
 * The default Controller for the Webcam Module. It's pretty much some webcams.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 28072012
 * @package MyRadio_Webcam
 */
$streams = MyRadio_Webcam::getStreams();

CoreUtils::getTemplateObject()->setTemplate('Webcam/grid.twig')
    ->addVariable('streams', $streams)
    ->render();
