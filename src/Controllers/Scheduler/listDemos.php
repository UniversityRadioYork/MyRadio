<?php
/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyRadio_Scheduler
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;

$demos = MyRadio_Demo::listDemos();

$twig = CoreUtils::getTemplateObject();

$tabledata = [];
foreach ($demos as $demo) {
    $demo['join'] = '<a href="'
        .CoreUtils::makeURL(
            'Scheduler',
            'attendDemo',
            ['demoid' => $demo['show_season_timeslot_id']]
        )
        .'">Join</a>';
    $tabledata[] = $demo;
}

if (empty($tabledata)) {
    $tabledata = [['','','','','Error' => 'There are currently no training slots available.']];
}

//print_r($tabledata);
$twig->setTemplate('table.twig')
    ->addVariable('title', 'Upcoming Training Slots')
    ->addVariable('tabledata', $tabledata)
    ->addVariable('tablescript', 'myury.scheduler.demolist');

if (isset($_REQUEST['msg'])) {
    switch ($_REQUEST['msg']) {
        case 0: //joined
            $twig->addInfo('You have successfully been added to this session.');
            break;
        case 1: //full
            $twig->addError('Sorry, but a maximum two people can join a session.');
            break;
        case 2: //attending already
            $twig->addError('You can only attend one session at a time.');
            break;
    }
}

$twig->render();
