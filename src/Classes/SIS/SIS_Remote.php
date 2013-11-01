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

	public static function query_messages($session) {
		// $response = SIS_Messages::getMessages($session['timeslotid'], $_REQUEST['messages_highest_id']);

		// if (!empty($response) && $response !== false) {
		// 	return array('messages' => $response);
		// }
		return $_REQUEST['messages_highest_id'];
	}
}