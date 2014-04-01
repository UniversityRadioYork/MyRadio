<?php

$twig = CoreUtils::getTemplateObject();

$tabledata = array();
foreach ($demos as $demo) {
    $demo['join'] = '<a href="'
        .CoreUtils::makeURL(
            'Scheduler',
            'attendDemo',
            array('demoid' => $demo['show_season_timeslot_id'])
        )
        .'">Join</a>';
    $tabledata[] = $demo;
}

if (empty($tabledata)) {
    $tabledata = array(array('','','','','Error' => 'There are currently no demo slots available.'));
}

//print_r($tabledata);
$twig->setTemplate('table.twig')
    ->addVariable('title', 'Upcoming Demo Slots')
    ->addVariable('tabledata', $tabledata)
    ->addVariable('tablescript', 'myury.scheduler.demolist');
if (isset($_REQUEST['msg'])) {
    switch($_REQUEST['msg']) {
        case 0: //joined
            $twig->addInfo('You have successfully been added to this demo.');
            break;
        case 1: //full
            $twig->addError('Sorry, but a maximum two people can join a demo.');
            break;
        case 2: //attending already
            $twig->addError('You can only attend one demo at a time.');
            break;
    }
}

$twig->render();
