<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc.... 
 * 
 * @todo Proper documentation
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130809
 * @package MyRadio_Core
 */

$user = MyRadio_User::getInstance();
$menu = (new MyRadioMenu())->getMenuForUser($user);

$news = MyRadioNews::getLatestNewsItem(Config::$news_feed, $user);

$twig = CoreUtils::getTemplateObject()->setTemplate('MyRadio/menu.twig')
        ->addVariable('title', 'Menu')
        ->addVariable('menu', $menu)
        ->addVariable('news_clickthrough', empty($news['seen']))
        /**
         * This is some bonus stuff for the Get On Air item
         */
        ->addVariable('studio_trained', $user->isStudioTrained())
        ->addVariable('studio_demoed', $user->isStudioDemoed())
        ->addVariable('is_trainer', $user->isTrainer())
        ->addVariable('has_show', $user->hasShow())
        ->addVariable('paid', $user->isCurrentlyPaid());

if (Config::$members_news_enable) {
    $twig->addVariable('news', $news);
}

$twig->render();