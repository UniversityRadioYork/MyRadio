<?php
/**
 * Selector Plugin for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130923
 * @package MyRadio_SIS
 */

$sel = new MyRadio_Selector();

$status = $sel->query();
$power = $status['power'];

$vars = array(
	'status' => $status,
	's1power' => ($power & 1),
	's2power' => ($power & 2) >> 1,
	's4power' => true,
	);

$moduleInfo = array(
'name' => 'selector',
'title' => 'Studio Selector',
'enabled' => true,
'startOpen' => false,
'help' => 'To the right, you can see a Studio Selector button. This contains a digital version of the magic buttons you can see in each studio. Don\'t worry - most members can\'t see this so they won\'t go messing with your show from the comfort of their own homes. You must have special priveleges if you can see this! Please use it carefully - it includes a fourth button, Outside Broadcast, which usually plays placeholder noises, which are a terrible substitute for a radio show.',
'vars' => $vars,
'required_permission' => AUTH_MODIFYSELECTOR,
'required_location' => false
);

  /**
   * @todo: check if the OB mount is available
   * @todo: $selectorStatusFile - use MyRadio_Selector
   */