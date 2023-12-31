<?php
namespace MyRadio\ServiceAPI;

use MyRadio\SIS\SIS_Messages; // Use correct case
use MyRadio\MyRadioException; // Import missing class

class MyRadio_Messages extends ServiceAPI
{
  public static function markread($id = null)
  {
    if ($id === null) {
      throw new MyRadioException('No message ID provided', 400); // Fix the problem by using the imported class
    }

    SIS_Messages::setMessageStatus(intval($id), SIS_Messages::MSG_STATUS_READ);

    return ['status' => 200, 'content' => 'Message marked as read.'];
  }
}