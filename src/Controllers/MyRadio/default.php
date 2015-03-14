<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc....
 *
 * @todo Direct usage of global variable $container here
 *
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\MyRadio\MyRadioMenu;
use \MyRadio\MyRadio\MyRadioNews;

$user = MyRadio_User::getInstance();
$menu = (new MyRadioMenu())->getMenuForUser();

$news = MyRadioNews::getLatestNewsItem($container['database'], $container['config']->news_feed, $user);
$news_clickthrough = $container['config']->members_news_enable && empty($news['seen']);

$twig = CoreUtils::getTemplateObject()->setTemplate('MyRadio/menu.twig')
        ->addVariable('title', 'Menu')
        ->addVariable('menu', $menu)
        ->addVariable('news_clickthrough', $news_clickthrough)
        /**
         * This is some bonus stuff for the Get On Air item
         */
        ->addVariable('studio_trained', $user->isStudioTrained())
        ->addVariable('studio_demoed', $user->isStudioDemoed())
        ->addVariable('is_trainer', $user->isTrainer())
        ->addVariable('has_show', $user->hasShow())
        ->addVariable('paid', $user->isCurrentlyPaid())
        ->addVariable('contract_signed', $user->hasSignedContract());

if ($container['config']->members_news_enable) {
    $twig->addVariable('news', $news);
}

$twig->render();
