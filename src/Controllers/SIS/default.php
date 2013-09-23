<?php
/**
 * Main renderer for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130923
 * @package MyURY_SIS
 */

CoreUtils::requireTimeslot();

  $template = 'SIS/main.twig';
  $title = 'SIS';
  

CoreUtils::getTemplateObject()->setTemplate($template)
        ->addVariable('title', $title)
        ->render();