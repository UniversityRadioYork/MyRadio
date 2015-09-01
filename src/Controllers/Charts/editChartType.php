<?php
/**
 * Allows the editing of chart types.
 * @package MyRadio_Charts
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_ChartType;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_ChartType::getform()->readValues();
    $chart_type = MyRadio_ChartType::getInstance($data['myradiofrmedid']);
    $chart_type->setName($data['name'])->setDescription($data['description']);

    URLUtils::backWithMessage('Chart Type Updated.');

} else {
    //Not Submitted
    if (!isset($_REQUEST['chart_type_id'])) {
        throw new MyRadioException('You must provide a chart_type_id', 400);
    }

    $chart_type = MyRadio_ChartType::getInstance($_REQUEST['chart_type_id']);
    $chart_type->getEditForm()->render();
}
