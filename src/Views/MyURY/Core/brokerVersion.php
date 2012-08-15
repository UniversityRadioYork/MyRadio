<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 15082012
 * @package MyURY_Core
 */
require 'Views/MyURY/bootstrap.php';
$twig->setTemplate('stripe.twig')
        ->addVariable('title', 'Version Selector')
        ->addVariable('heading', 'Version Selector')
        ->addVariable('versions', $versions)
        ->render();