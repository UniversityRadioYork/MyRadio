<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc....
 *
 * @todo Proper documentation
 */
use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use MyRadio\ServiceAPI\MyRadio_Event;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\MyRadio\MyRadioMenu;
use \MyRadio\MyRadio\MyRadioNews;

/** @var MyRadio_User $user */
$user = MyRadio_User::getInstance();
$menu = (new MyRadioMenu())->getMenuForUser();

$news = MyRadioNews::getLatestNewsItem(Config::$news_feed, $user);
$news_clickthrough = Config::$members_news_enable && empty($news['seen']);

$events = MyRadio_Event::getNext(3);

$twig = CoreUtils::getTemplateObject()->setTemplate('MyRadio/menu.twig')
        ->addVariable('title', 'Welcome to '.Config::$short_name.', '. $user->getFName(). '!')
        ->addVariable('menu', $menu)
        ->addVariable('news_clickthrough', $news_clickthrough)
        ->addVariable('events', $events);

if (Config::$members_news_enable) {
    $twig->addVariable('news', $news);
}

$twig->render();
