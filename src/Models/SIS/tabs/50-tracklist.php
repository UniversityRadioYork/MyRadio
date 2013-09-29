<?php
/**
 * Track Listing Tab for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130925
 * @package MyURY_SIS
 */

$moduleInfo = array(
'name' => 'tracklist',
'title' => 'Track Listing',
'enabled' => true,
'help' => 'Tracklisting is a legal requirement for URY to broadcast, so you must fill this in. If you use BAPS this will be done automatically, but if you use other sources you must fill this in yourself',
'template' => 'SIS/tabs/tracklist.twig',
);

/**
 * @todo: query_bapslog
 */