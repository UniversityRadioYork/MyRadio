<?php
/**
 * Presenter Infomation Tab for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130925
 * @package MyURY_SIS
 */

$vars = array(
	'piss' => MyURYNews::getNewsItem(Config::$piss_feed, User::getInstance())
	);

$moduleInfo = array(
'name' => 'piss',
'title' => 'Presenter Information',
'enabled' => true,
'help' => 'Please read this before the start of your show. It\'s among the tabs up at the top and provides lots of useful information from our great lord and master, <station manager name>. It\'s a great way to find out how to get more involved in URY or see what you events you can advertise on your show.',
'template' => 'SIS/tabs/piss.twig',
'vars' => $vars
);