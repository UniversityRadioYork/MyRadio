<?php

/**
 * This file provides the MyRadioNews class for MyRadio
 * @package MyRadio_Core
 */

/**
 * Description of MyRadioNews
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130718
 * @package MyRadio_Core
 * @uses \CacheProvider
 * @uses \Database
 * @uses \CoreUtils
 * @todo Refactor to classes and ServiceAPI
 */
class MyRadioNews
{
    public function __construct()
    {
    }

    /**
     * Returns all items in the given feed
     * @param int $newsfeedid
     */
    public static function getFeed($newsfeedid, MyRadio_User $user = null, $revoked = false)
    {
        $data = [];
        foreach (Database::getInstance()->fetchColumn(
            'SELECT newsentryid FROM public.news_feed'
            . ' WHERE feedid=$1' .($revoked ? '' : ' AND revoked=false'),
            [$newsfeedid]
        ) as $row) {

            $data[] = self::getNewsItem($row, $user);
        }

        return $data;
    }

    /**
     * Returns the latest news item for the given feed, and if given a user, the timestamp of when they saw it
     * @param  id           $newsfeedid The ID of the newsfeed to check
     * @param  MyRadio_User $user       The User object to check if seen. Default null, won't return a seen column.
     * @return Array
     */
    public static function getLatestNewsItem($newsfeedid, MyRadio_User $user = null)
    {
        $result = Database::getInstance()->fetchColumn(
                'SELECT newsentryid FROM public.news_feed
                WHERE public.news_feed.feedid=$1 AND revoked=false
                ORDER BY timestamp DESC LIMIT 1',
                [$newsfeedid]
            );
        $item = empty($result) ? null : $result[0];
        return self::getNewsItem(
            $item,
            $user
        );
    }

    public static function getNewsItem($newsentryid, MyRadio_User $user = null)
    {
        $db = Database::getInstance();

        $news = $db->fetchOne(
            'SELECT newsentryid, fname || \' \' || sname AS author, timestamp AS posted, content
            FROM public.news_feed, public.member
            WHERE newsentryid=$1
            AND news_feed.memberid = member.memberid',
            [$newsentryid]
        );

        if (empty($news)) {
            return null;
        }

        return array_merge(
            $news,
            [
                'seen' => $db->fetchOne(
                    'SELECT seen FROM public.member_news_feed
                    WHERE newsentryid=$1 AND memberid=$2 LIMIT 1',
                    [
                        $news['newsentryid'],
                        empty($user) ? 0 : $user->getID()
                    ]
                ),
                'posted' => CoreUtils::happyTime($news['posted'])
            ]
        );
    }

    /**
     * @todo Document this
     * @param type         $newsentryid
     * @param MyRadio_User $user
     */
    public static function markNewsAsRead($newsentryid, MyRadio_User $user)
    {
        $db = Database::getInstance();

        try {
            $db->query('INSERT INTO public.member_news_feed (newsentryid, memberid) VALUES ($1, $2)', [$newsentryid, $user->getID()]);
        } catch (MyRadioException $e) {

        }; //Can sometimes get duplicate key errors
    }

    public static function addItem($feedid, $content)
    {
        Database::getInstance()->query(
            'INSERT INTO public.news_feed'
            . ' (feedid, memberid, content) VALUES'
            . ' ($1, $2, $3)',
            [$feedid, $_SESSION['memberid'], $content]
        );
    }

    public static function getForm()
    {
        return (
            new MyRadioForm(
                'myradio_news',
                'MyRadio',
                'addNews',
                [
                    'title' => 'Add news item'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'body',
                MyRadioFormField::TYPE_BLOCKTEXT,
                [
                    'explanation' => '',
                    'label' => 'Content'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'feedid',
                MyRadioFormField::TYPE_HIDDEN
            )
        );
    }

}
