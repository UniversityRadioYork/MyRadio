<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130711
 * @package MyURY_Core
 */
CoreUtils::getTemplateObject()->setTemplate('MyURY/menu.twig')
        ->addVariable('title', 'Menu')
        ->addVariable('menu', $menu)
        ->addVariable('news', $news)
        ->addVariable('news_clickthrough', empty($news['seen']))
        /**
         * This is some bonus stuff for the Get On Air item
         */
        ->addVariable('studio_trained', $member->isStudioTrained())
        ->addVariable('studio_demoed', $member->isStudioDemoed())
        ->addVariable('is_trainer', $member->isTrainer())
        ->addVariable('has_show', $member->hasShow())
        ->render();