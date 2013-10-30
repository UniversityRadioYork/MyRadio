<?php
/**
 * Messages Tab for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130925
 * @package MyRadio_SIS
 */


function query_messages($session) {
  $response = SIS_Messages::getMessages($session['timeslotid'], $_REQUEST['messages_highest_id']);

  if (!empty($response) && $response !== false) {
    return array('messages' => $response);
  }
}

$moduleInfo = array(
'name' => 'messages',
'title' => 'Messages',
'enabled' => true,
'help' => 'This is the big one, probably where you will spen most of your time in SIS. The Message tab provides you with all the comunication you can get with the listener, whether the message "Via the website" or text the studio it all comes here.',
'template' => 'SIS/tabs/messages.twig',
'pollfunc' => 'query_messages'
);
