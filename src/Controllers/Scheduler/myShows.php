<?php
/**
 * Lists Shows the User has
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130828
 * @package MyRadio_Scheduler
 */

$shows = MyRadio_User::getInstance()->getShows();

//This is a Joyride start point - if there are no shows, or it's their first season, run the first show joyride.
if (empty($shows) or (sizeof($shows) === 1 and sizeof($shows[0]->getAllSeasons()) === 0)) {
    $_SESSION['joyride'] = 'first_show';
}

$twig = CoreUtils::getTemplateObject()->setTemplate('Scheduler/myShows.twig')
    ->addVariable('title', 'My Shows')
    ->addVariable('tabledata', ServiceAPI::setToDataSource($shows))
    ->addVariable('tablescript', 'myury.scheduler.showlist');

if (isset($_REQUEST['msg'])) {
    switch ($_REQUEST['msg']) {
        case 'seasonCreated':
            $twig->addInfo('Your season application has been submitted for processing.');
            break;
    }
}

$twig->render();
