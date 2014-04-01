<?php
/**
 * Messages Tab for SIS
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130925
 * @package MyRadio_SIS
 */


$moduleInfo = array(
    'name' => 'messages',
    'title' => 'Messages',
    'enabled' => true,
    'help' => 'This is the big one, probably where you will spend most of your time in SIS. '
        .'The Message tab provides you with all the comunication you can get with the listener, '
        .'whether the message "Via the website" or text the studio it all comes here.',
    'pollfunc' => 'SIS_Remote::query_messages'
);
