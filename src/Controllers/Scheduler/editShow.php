<?php
/**
 * This is the magic form that makes URY actually have content - it enables users to apply for shows. And stuff.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130728
 * @package MyURY_Scheduler
 */

//The Form definition
require 'Models/Scheduler/showfrm.php';
$form->editMode($_REQUEST['showid'])
     ->setTemplate('Scheduler/createShow.twig')
        ->render();