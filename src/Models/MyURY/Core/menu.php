<?php
/**
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 */
$menu = (new MyURYMenu())->getMenuForUser($member);

$news = CoreUtils::getNewsItem(Config::$news_feed, $member);
print_r($news);