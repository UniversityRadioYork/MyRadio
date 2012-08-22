<?php

/*
 * Provides the Album class for MyURY
 * Based and bashed around from the Artist class, so if there was a problem with that (12/Aug/2012), then it's likely to be here as well
 * @package MyURY_Core
 */

/*
 * The Album class fetches information about albums in the Cental Databse. It may be expanded to deal with entering and modifying them at some point as well.
 * @version 12082012
 * @author Anthony Williams <anthony@ury.york.ac.uk>
 * @todo EVERYTHING
 * @package MyURY_Core
 * @uses \Database
 * 
 */
class Artist extends ServiceAPI {
	private static $albums = array();

	private function __construct($albumid){
		$this->albumid = $albumid;
		throw new MyURYException('Not implemented Album::__construct');
	}
	public static function findByName($title, $limit){
		$title = trim($title);
		return self::$db->fetch_all('SELECT DISTINCT rec_record.recordid AS recordid, FROM rec_record WHERE rec_record.title ILIKE \'%\' || $1 || \'%\' LIMIT $2;', array($title, $limit));
	}

	public static function getAlbumDetails($title){
		$title = trim($title);
		return self::$db->fetch_all('SELECT DISTINCT rec_record.title AS title, rec_record.artist AS artist, rec_record.status AS status FROM rec_record WHERE rec_record.recordid = $1;', array($title, $limit));
	}
	
}