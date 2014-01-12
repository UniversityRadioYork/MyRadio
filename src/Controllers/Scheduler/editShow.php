<?php
/**
 * This page enables a Users to edit a Show that already exists. It takes one parameter, $_REQUEST['showid']
 * which should be the ID of the Show to edit.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130728
 * @package MyRadio_Scheduler
 */

//Check the user has permission to edit this show
$show = MyRadio_Show::getInstance($_REQUEST['showid']);
if (!$show->isCurrentUserAnOwner() && !CoreUtils::hasPermission(AUTH_EDITSHOWS)) {
  $message = 'You must be a Creditor of a Show or be in the Programming Team to edit this show.';
  require 'Views/Errors/403.php';
}

//The Form definition
require 'Models/Scheduler/showfrm.php';

$meta = $show->getMeta('tag');
if ($meta === null) {
    $meta = array();
}
$form->editMode($_REQUEST['showid'], array(
            'title' => $show->getMeta('title'),
            'description' => $show->getMeta('description'),
            'genres' => $show->getGenre(),
            'tags' => implode(' ', $meta),
            'credits.member' => array_map(function ($ar) {
                      return $ar['User'];
                    }, $show->getCredits()),
            'credits.credittype' => array_map(function ($ar) {
                      return $ar['type'];
                    }, $show->getCredits()),
            'mixclouder' => ($show->getMeta('upload_state') === 'Requested')
                ),
          'doEditShow'
        )
        ->render();