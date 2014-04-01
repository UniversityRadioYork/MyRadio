<?php
/**
 * This page enables a Users to edit a Season that already exists.
 * It takes one parameter, $_REQUEST['seasonid']
 * which should be the ID of the Show to edit.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130923
 * @package MyRadio_Scheduler
 */

//Check the user has permission to edit this show
$season = MyRadio_Season::getInstance($_REQUEST['seasonid']);
if (!$season->getShow()->isCurrentUserAnOwner() && !CoreUtils::hasPermission(AUTH_EDITSHOWS)) {
    $message = 'You must be a Creditor of the Show or be in the Programming Team to edit this season.';
    require 'Views/Errors/403.php';
}

//The Form definition
require 'Models/Scheduler/showfrm.php';

$form->editMode(
    $_REQUEST['seasonid'],
    array(
        'title' => $season->getMeta('title'),
        'description' => $season->getMeta('description'),
        'tags' => implode(' ', $season->getMeta('tag')),
        'credits.member' => array_map(
            function ($ar) {
                return $ar['User'];
            },
            $season->getCredits()
        ),
        'credits.credittype' => array_map(
            function ($ar) {
                return $ar['type'];
            },
            $season->getCredits()
        )
    ),
    'doEditSeason'
)->setTitle('Edit Season of '.$season->getShow()->getMeta('title'))
->render();
