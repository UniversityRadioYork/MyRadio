<?php
/**
 * Selector Plugin for SIS
 *
 * @author  Andy Durant <aj@ury.org.uk>
 * @version 20130923
 * @package MyRadio_SIS
 */

$moduleInfo = [
    'name' => 'selector',
    'title' => 'Studio Selector',
    'enabled' => true,
    'startOpen' => false,
    'pollfunc' => '\MyRadio\SIS\SIS_Remote::querySelector',
    'help' => 'To the left, you can see a Studio Selector button. '
        .'This contains a digital version of the magic buttons you can see in each studio. '
        .'Don\'t worry - most members can\'t see this so they won\'t go messing with your show from the comfort of '
        .'their own homes. You must have special priveleges if you can see this! Please use it carefully - '
        .'it includes a fourth button, Outside Broadcast, which usually plays placeholder noises, '
        .'which are a terrible substitute for a radio show.',
    'required_permission' => AUTH_MODIFYSELECTOR,
    'required_location' => false
];

  /**
   * @todo: check if the OB mount is available
   * @todo: $selectorStatusFile - use MyRadio_Selector
   */
