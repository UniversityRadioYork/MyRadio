<?php
/**
 * Edit a Banner.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Banner;
use \MyRadio\MyRadioException;


  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = MyRadio_Banner::getDeleteForm()->readValues();
    if (isset($data['bannerid'])) {
      MyRadio_Banner::getInstance($data['bannerid'])
        ->deleteBanner();

      //shouldn't be doing this.'
      URLUtils::backWithMessage('Banner Deleted! (Not really)');
    } else {
      //create form
      throw new MyRadioException('A Banner ID was not provided by the form for deletion. Please try again.', 'w');
    }

  } else {
      //Not Submitted
    if (isset($_REQUEST['bannerid'])) {

          //delete form
          $banner = MyRadio_Banner::getInstance($_REQUEST['bannerid']);

          MyRadio_Banner::getDeleteForm()
            ->setFieldValue('bannerid', $banner->getBannerID())
            ->render();
    } else {
            //create form
            throw new MyRadioException('A Banner ID was not provided for deletion. Please try again.', 'w');
    }
  }
