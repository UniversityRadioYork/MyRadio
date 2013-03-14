<?php
/**
 * Main renderer for NIPSWeb
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 11032013
 * @package MyURY_NIPSWeb
 */
require 'Views/MyURY/bootstrap.php';

$twig->setTemplate('NIPSWeb/menu.twig')
        ->addVariable('title', 'Show Planner')
        ->addVariable('heading', 'Show Planner')
        ->addVariable('tracks', MyURY_Timeslot::getInstance($_SESSION['timeslotid'])->getShowPlan())
        ->render();