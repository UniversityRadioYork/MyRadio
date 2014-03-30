<?php
/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130809
 * @package MyRadio_Core
 */
header('HTTP/1.1 404 File Not Found');

CoreUtils::getTemplateObject()->setTemplate('error.twig')
        ->addVariable('serviceName', 'Error')
        ->addVariable('title', 'I\'m not the page you\'re looking for')
        ->addVariable('body', '<p>I\'m sorry, but the Module or Action you are looking for doesn\'t seem to exist.</p>
          <img src="'.Config::$base_url.'img/small-vegetables.jpg" /><p>Remember to Always Eat Your Vegetables</p>')
        ->render();
