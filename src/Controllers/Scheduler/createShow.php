<?php

/**
 * This is the magic form that makes URY actually have content - it enables users to apply for shows. And stuff.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130727
 * @package MyRadio_Scheduler
 */
//The Form definition
require 'Models/Scheduler/showfrm.php';
$form->setFieldValue('credits.member', array(User::getInstance()))
        ->setFieldValue('credits.credittype', array(1))
        ->setTemplate('Scheduler/createShow.twig')
        ->render();