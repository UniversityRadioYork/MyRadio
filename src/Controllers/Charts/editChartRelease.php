<?php
/**
 * Allows the editing of chart releases.
 * @version 20140113
 * @author  Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Charts
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_ChartRelease;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_ChartRelease::getForm()->readValues();

    if (empty($data['id'])) {
        //create new
        $chart_release = MyRadio_ChartRelease::create($data);
    } else {
        //submit edit
        $chart_release = MyRadio_ChartRelease::getInstance($data['id']);
        $chart_release
            ->setChartTypeID($data['chart_type_id'])
            ->setReleaseTime($data['submitted_time']);
    }

    foreach ($data['tracks']['track'] as $track) {
        if (is_object($track)) {
            $tracks[] = $track->getID();
        }
    }

    $chart_release->setChartRows($tracks);

    CoreUtils::backWithMessage('Chart Release Updated.');

} else {
    //Not Submitted
    if (isset($_REQUEST['chart_release_id'])) {
        //edit form
        MyRadio_ChartRelease::getInstance($_REQUEST['chart_release_id'])
            ->getEditForm()
            ->render();

    } else {
        //create form
        MyRadio_ChartRelease::getForm()
            ->setFieldValue('submitted_time', CoreUtils::happyTime(time(), false))
            ->render();
    }
}
