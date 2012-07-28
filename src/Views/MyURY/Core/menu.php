<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 */
require 'Views/MyURY/bootstrap.php';
$twig->setTemplate('MyURY/menu.twig')
        ->addVariable('title', 'Menu')
        ->addVariable('heading', 'Menu')
        ->addVariable('menu', $menu)
        ->addVariable('news', $news)
        ->addVariable('news_clickthrough', empty($news['seen']))
        /**
         * This is some bonus stuff for the Get On Air item
         */
        ->addVariable('studio_trained', $member->isStudioTrained())
        ->addVariable('studio_demoed', $member->isStudioDemoed())
        ->addVariable('has_show', $member->hasShow())
        ->render();