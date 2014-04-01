<?php
/**
 * Performs the actual editing of chart releases.
 * @version 20131002
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Charts
 */

/*
 * Creates a new chart row.
 *
 * @param $chart_release_id  The numeric ID of the chart release to which this
 *                           row belongs.
 * @param $position          The position in the chart of this row (from 1).
 * @param $track             The MyRadio_Track this row contains.
 *
 * @return Nothing.  This function writes directly to the database.
 */
function create_chart_row($chart_release_id, $position, $track)
{
    MyRadio_ChartRow::create(
        [
              'chart_release_id' => $chart_release_id,
              'position' => $position,
              'trackid' => $track->getID()
        ]
    );
}

/*
 * Creates a new chart release.
 *
 * @param $data  The data hash from the chart releases form.
 *
 * @return Nothing.  This function writes directly to the database.
 */
function create_chart_release($data)
{
    MyRadio_ChartRelease::create($data);
    $chart_release_id = MyRadio_ChartRelease::findReleaseIDOn(
        $data['submitted_time'],
        $data['chart_type_id']
    );

    for ($i = 1; $i <= 10; $i++) {
        create_chart_row($chart_release_id, $i, track_at($i, $data));
    }
}

/*
 * Edits the given chart row.
 *
 * @param $chart_row  The chart row to edit.
 * @param $track      The new track for the chart row.
 *
 * @return Nothing.  This function writes directly to the database.
 */
function edit_chart_row($chart_row, $track)
{
    $chart_row->setTrackID($track->getID());
}

/*
 * Edits the chart release with the given ID.
 *
 * @param $id    The ID of the chart release to edit.
 * @param $data  The data hash from the chart releases form.
 *
 * @return Nothing.  This function writes directly to the database.
 */
function edit_chart_release($id, $data)
{
    $chart_release = MyRadio_ChartRelease::getInstance($id);
    $chart_release->setChartTypeID(
        $data['chart_type_id']
    )->setReleaseTime($data['submitted_time']);

    // TODO: Handle existing chart releases with differing numbers of chart rows.
    // Currently, this case will explode dramatically.
    foreach ($chart_release->getChartRows() as $chart_row) {
        edit_chart_row($chart_row, track_at($chart_row->getPosition(), $data));
    }
}

/*
 * Gets the track for the given position from the data.
 *
 * @param $position  The position whose track is sought (starting from 1).
 * @param $data      The dataset containing the tracks.
 *
 * @return MyRadio_Track the track at the given position.
 */
function track_at($position, $data)
{
    return $data['track' . $position];
}

/*
 * END OF HELPER FUNCTIONS
 */

$form = MyRadio_JsonFormLoader::loadFromModule(
    $module,
    'chartreleasefrm',
    'doEditChartRelease',
    ['chart_types' => []]
);

$data = $form->readValues();

if (empty($data['id'])) {
    create_chart_release($data);
} else {
    edit_chart_release($data['id'], $data);
}

CoreUtils::redirect($module);
