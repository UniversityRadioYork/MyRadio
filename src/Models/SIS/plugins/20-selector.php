<?php
/**
 * Selector Plugin for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130923
 * @package MyURY_SIS
 */

$moduleInfo = array(
'name' => 'selector',
'title' => 'Studio Selector',
'enabled' => true,
'startOpen' => false,
'help' => 'To the right, you can see a Studio Selector button. This contains a digital version of the magic buttons you can see in each studio. Don\'t worry - most members can\'t see this so they won\'t go messing with your show from the comfort of their own homes. You must have special priveleges if you can see this! Please use it carefully - it includes a fourth button, Outside Broadcast, which usually plays placeholder noises, which are a terrible substitute for a radio show.',
'template' = 'SIS/plugins/selector.twig',
'vars' => $vars,
'required_permission' = AUTH_MODIFYSELECTOR,
'required_location' = false
)

$lastmod = @filemtime(Config::$selectorStatusFile);
$status = @file(Config::$selectorStatusFile);

$vars = array(
	'lastmod' => $lastmod,
	'status' => $status,
	'onair' => (int)$status[0][0],
	'power' => (int)$status[0][3],
	's1power' => (int)(($power & 1) != 0),
	's2power' => (int)(($power & 2) != 0),
	's4power' => true,
	)

  /**
   * @todo: check if the OB mount is available
   * @todo: $selectorStatusFile - use MyURY_Selector
   */