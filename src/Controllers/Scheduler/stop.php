<?php
/**
 * This stops everything. It's part of a several-stage process to trigger
 * the station's emergency broadcast system.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20131215
 * @package MyRadio_Scheduler
 */

$result = true;
$stage = isset($_POST['stage']) ? $_POST['stage'] : 1;

if ($stage == 3) {
    $title = $_POST['show-name'];
    $shows = MyRadio_User::getInstance()->getShows();
    if (empty($shows)) {
        $result = false;
        $stage--;
    } else {
        if (strtolower($shows[0]->getMeta('title')) !== strtolower($title)) {
            $result = false;
            $stage--;
        } else {
            $result = MyRadio_User::getInstance()->getEduroam();
        }
    }
}

if ($stage == 0) {
    $sel = new MyRadio_Selector();
    $sel->startObit();
}

CoreUtils::getTemplateObject()->setTemplate('Scheduler/stop.twig')
        ->addVariable('title', 'Stop Broadcast')
        ->addVariable('stage', $stage)
        ->addVariable('result', $result)
        ->render();
