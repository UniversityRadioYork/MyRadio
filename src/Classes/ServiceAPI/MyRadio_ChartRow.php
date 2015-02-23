<?php
/**
 * Provides the MyRadio_ChartRow class for MyRadio
 * @package MyRadio_Charts
 */

namespace MyRadio\ServiceAPI;

use \MyRadio\MyRadioException;

/**
 * The ChartRow class fetches information about rows of chart releases.
 * @version 20130426
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Charts
 * @uses \Database
 */
class MyRadio_ChartRow extends ServiceAPI
{
    /**
     * The singleton store for ChartRow objects
     * @var MyRadio_ChartRow[]
     */
    private static $chart_rows = [];

    /**
     * The numeric ID of the chart row.
     * @var Int
     */
    private $chart_row_id;

    /**
     * The position on the chart release this row occupies.
     * @var Int
     */
    private $position;

    /**
     * The ID of the track at this position.
     * @var String
     */
    private $trackid;

    /**
     * Constructs a new MyRadio_ChartRow from the database.
     *
     * You should generally use MyRadio_ChartRow::getInstance instead.
     *
     * @param $chart_row_id   The numeric ID of the chart row.
     * @param $chart_release  The parent chart release, if any.
     *
     * @return The chart row with the given ID.
     */
    protected function __construct($chart_row_id, $chart_release = null)
    {
        $this->chart_row_id = $chart_row_id;
        $this->chart_release = $chart_release;

        $chart_row_data = self::$db->fetchOne(
            'SELECT *
            FROM music.chart_row
            WHERE chart_row_id = $1;',
            [$chart_row_id]
        );
        if (empty($chart_row_data)) {
            throw new MyRadioException('The specified Chart Row does not seem to exist.');

            return;
        }

        $this->position = intval($chart_row_data['position']);
        $this->trackid = intval($chart_row_data['trackid']);
    }

    /**
     * Retrieves the MyRadio_ChartRow with the given numeric ID.
     *
     * @param $chart_row_id  The numeric ID of the chart row.
     */
    public static function getInstance($chart_row_id = -1)
    {
        self::__wakeup();

        if (!is_numeric($chart_row_id)) {
            throw new MyRadioException(
                'Invalid Chart Row ID!',
                MyRadioException::FATAL
            );
        }

        if (!isset(self::$chart_rows[$chart_row_id])) {
            self::$chart_rows[$chart_row_id] = new self($chart_row_id);
        }

        return self::$chart_rows[$chart_row_id];
    }

    /**
     * Retrieves the unique ID of this chart row.
     *
     * @return The chart row ID.
     */
    public function getID()
    {
        return $this->chart_row_id;
    }

    /**
     * Returns the chart row's track ID.
     * @return int The unique integral ID of the chart row track.
     */
    public function getTrackID()
    {
        return $this->trackid;
    }

    /**
     * Returns the chart row's track.
     *
     * Will perform one database query, most likely.
     *
     * @return MyRadio_Track The track this chart row represents.
     */
    public function getTrack()
    {
        return MyRadio_Track::getInstance($this->getTrackID());
    }

    /**
     * Returns the chart row's position.
     * @return The position on the chart release this row occupies.
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Creates a new chart row in the database.
     *
     * @param  $data array  An array of data to populate the row with.
     *                     Must contain 'position', 'chart_release_id' and
     *                     'trackid'.
     * @return MyRadio_ChartRow The newly created track.
     */
    public function create($data)
    {
        self::$db->query(
            'INSERT INTO music.chart_row(chart_release_id, position, trackid)
             VALUES ($1, $2, $3);',
            [
                $data['chart_release_id'],
                $data['position'],
                $data['trackid']
            ],
            true
        );
    }

    /**
     * Sets this chart row's track ID.
     *
     * @param int $trackid The new track ID.
     *
     * @return This object, for method chaining.
     */
    public function setTrackID($trackid)
    {
        $this->trackid = intval($trackid);

        self::$db->query(
            'UPDATE music.chart_row
             SET trackid = $1
             WHERE chart_row_id = $2;',
            [$trackid, $this->getID()]
        );

        return $this;
    }
}
