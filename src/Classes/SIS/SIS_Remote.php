<?php

/*
 * This file provides the SIS_Remote class for MyRadio
 * @package MyRadio_SIS
 */

/**
 * This class has helper functions for long-polling SIS
 * 
 * @version 20131101
 * @author Andy Durant <aj@ury.org.uk>
 * @package MyRadio_SIS
 */
class SIS_Remote extends ServiceAPI {

	/**
	 * Gets the latest messages for the selected timeslot
	 * @param  array $session phpSession variable
	 * @return array          message data
	 */
	public static function query_messages($session) {
		$response = SIS_Messages::getMessages($session['timeslotid'], isset($_REQUEST['messages_highest_id']) ? $_REQUEST['messages_highest_id'] : 0);

		 if (!empty($response) && $response !== false) {
		 	return array('messages' => $response);
		 }
	}


	public static function query_tracklist($session) {
		$response = SIS_Tracklist::getTrackListing($session['timeslotid'], isset($_REQUEST['tracklist_highest_id']) ? $_REQUEST['tracklist_highest_id'] : 0);

		if (!empty($response) && $response !== false) {
			return array('tracklist' => $response);
		}
	
	}
}