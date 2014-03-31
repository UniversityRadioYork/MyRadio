<?php
/**
 * This is the magic form that makes URY actually have content - it enables users to apply for seasons. And stuff.
 *
 * @todo Security check to see if this user is allowed to apply for seasons for this show
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 23082012
 * @package MyRadio_Scheduler
 */

//The Form definition
$current_term_info = MyRadio_Scheduler::getActiveApplicationTermInfo();
$current_term = $current_term_info['descr'];
require 'Models/Scheduler/seasonfrm.php';
$form->setFieldValue('show_id', (int) $_REQUEST['showid'])
     ->setTemplate('Scheduler/createSeason.twig')
     ->render(array('current_term' => $current_term));
