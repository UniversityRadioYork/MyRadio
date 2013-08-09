<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc.... 
 * 
 * @todo Proper documentation
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130809
 * @package MyURY_Core
 */

$menu = (new MyURYMenu())->getMenuForUser(User::getInstance());

$news = MyURYNews::getNewsItem(Config::$news_feed, User::getInstance());

require 'Views/MyURY/menu.php';