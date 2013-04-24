<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 */
header('HTTP/1.1 404 File Not Found');

$twig->setTemplate('error.twig')
        ->addVariable('serviceName', 'Error')
        ->addVariable('title', 'File Not Found')
        ->addVariable('heading', 'I\'m not the page you\'re looking for')
        ->addVariable('body', '<p>I\'m sorry, but the Action, Module or Service you are looking for doesn\'t seem to exist.</p>
          <img src="img/small-vegetables.jpg" style="height:75%" /><p>Remember to Always Eat Your Vegetables</p>')
        ->render();