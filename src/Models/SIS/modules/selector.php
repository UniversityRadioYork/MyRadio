<?php
/**
 * Selector Plugin for SIS
 *
 * @package MyRadio_SIS
 */
$moduleInfo = [
    'pollfunc' => '\MyRadio\SIS\SIS_Remote::querySelector',
    'required_permission' => AUTH_MODIFYSELECTOR
];

  /**
   * @todo: check if the OB mount is available
   * @todo: $selectorStatusFile - use MyRadio_Selector
   */
