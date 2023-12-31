<?php
namespace MyRadio\ServiceAPI;

use MyRadio\SIS\SIS_Messages; // Use correct case

class MyRadio_Messages extends ServiceAPI
{
  public function markRead($messageId)
  {
    SIS_Messages::setMessageStatus(intval($_GET['id']), SIS_Messages::MSG_STATUS_READ);
    header('HTTP/1.1 204 No Content');
  }
}