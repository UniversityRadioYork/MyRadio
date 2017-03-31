<?php
/**
 * Provides the MyRadio_ChartRelease class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;

/**
 * The ChartRelease class fetches information about chart releases.
 *
 * A chart release is a chart for a given week, and is associated with a chart
 * and an arbitrary number of rows (traditionally 10).
 *
 * @uses    \Database
 */
class MyRadio_ChartRelease extends ServiceAPI
{
    const GET_INSTANCE_SQL = '
        SELECT
            *
        FROM
            music.chart_release
        WHERE
            chart_release_id = $1
        ;';

    const GET_CHART_ROWS_SQL = '
        SELECT
            chart_row_id
        FROM
            music.chart_row
        WHERE
            chart_release_id = $1
        ORDER BY
            position ASC
        ;';

    const FIND_RELEASE_ID_ON_SQL = '
        SELECT
            chart_release_id
        FROM
            music.chart_release
        WHERE
            chart_type_id = $1 AND
            submitted     = $2
        LIMIT
            1
        ;';

    const INSERT_SQL = '
        INSERT INTO
            music.chart_release(chart_type_id, submitted)
        VALUES
            ($1, $2)
        RETURNING
            chart_release_id
        ;';

    const SET_RELEASE_TIME_SQL = '
        UPDATE
            music.chart_release
        SET
            submitted = $1
        WHERE
            chart_release_id = $2
        ;';

    const SET_CHART_TYPE_ID_SQL = '
        UPDATE
            music.chart_release
        SET
            chart_type_id = $1
        WHERE
            chart_release_id = $2
        ;';

    /**
     * The singleton store for ChartRelease objects.
     *
     * @var MyRadio_ChartRelease[]
     */
    private static $chart_releases = [];

    /**
     * The chart type this chart release was released under.
     *
     * @var MyRadio_ChartType
     */
    private $chart_type;

    /**
     * The numeric ID of the chart type.
     *
     * @var int
     */
    private $chart_type_id;

    /**
     * The numeric ID of the chart release.
     *
     * @var int
     */
    private $chart_release_id;

    /**
     * The UNIX timestamp, if any, on which this chart release was released.
     *
     * @var int
     */
    private $release_time;

    /**
     * The list of IDs of MyRadio_ChartRows for this chart release.
     *
     * @var int[]
     */
    private $chart_row_ids;

    /**
     * Constructs a new MyRadio_ChartRelease from the database.
     *
     * You should generally use MyRadio_ChartRelease::getInstance instead.
     *
     * @param $chart_release_id  The numeric ID of the chart release.
     * @param $chart_type        The parent chart type, if any.
     *
     * @return The chart release with the given ID.
     */
    protected function __construct($chart_release_id, $chart_type = null)
    {
        $this->chart_release_id = (int) $chart_release_id;
        $this->chart_type = $chart_type;

        $chart_release_data = self::$db->fetchOne(
            self::GET_INSTANCE_SQL,
            [$chart_release_id]
        );
        if (empty($chart_release_data)) {
            throw new MyRadioException('The specified Chart Release does not seem to exist.');

            return;
        }

        $this->release_time = strtotime($chart_release_data['submitted']);
        $this->chart_type_id = $chart_release_data['chart_type_id'];

        $this->chart_row_ids = self::$db->fetchColumn(
            self::GET_CHART_ROWS_SQL,
            [$chart_release_id]
        );
    }

    /**
     * Retrieves the MyRadio_ChartRelease with the given numeric ID.
     *
     * @param $chart_release_id  The numeric ID of the chart release.
     * @param $chart_type        The parent chart type, if any.
     *
     * @return The chart release with the given ID.
     */
    public static function getInstance($chart_release_id = -1, $chart_type = null)
    {
        self::wakeup();

        if (!is_numeric($chart_release_id)) {
            throw new MyRadioException(
                'Invalid Chart Release ID!',
                MyRadioException::FATAL
            );
        }

        if (!isset(self::$chart_releases[$chart_release_id])) {
            self::$chart_releases[$chart_release_id] = new self(
                $chart_release_id,
                $chart_type
            );
        }

        return self::$chart_releases[$chart_release_id];
    }

    /**
     * Retrieves the ID of the (first) chart released on the given date on the
     * given chart type.
     *
     * This is mainly useful for finding a newly created chart type's ID.
     *
     * @param int $release_time  The release time, as a UNIX timestamp.
     * @param int $chart_type_id The ID of the chart type to search in.
     *
     * @return int The first chart released on the given time for
     *             the given type.
     */
    public function findReleaseIDOn($release_time, $chart_type_id)
    {
        return array_pop(
            self::$db->fetchColumn(
                self::FIND_RELEASE_ID_ON_SQL,
                [
                    $chart_type_id,
                    date('c', $release_time),
                ]
            )
        );
    }

    /**
     * Retrieves the time of release of this chart release.
     *
     * @return int the submission time as a UNIX timestamp.
     */
    public function getReleaseTime()
    {
        return $this->release_time;
    }

    /**
     * Retrieves the unique ID of this chart release.
     *
     * @return int The chart release ID.
     */
    public function getID()
    {
        return $this->chart_release_id;
    }

    /**
     * Retrieves the unique ID of this chart release's type.
     *
     * @return int The chart type ID.
     */
    public function getChartTypeID()
    {
        return $this->chart_type_id;
    }

    /**
     * Retrieves the type this chart release falls under.
     *
     * @return MyRadio_ChartType The chart type object.
     */
    public function getChartType()
    {
        if ($this->chart_type === null) {
            $this->chart_type = MyRadio_ChartType::getInstance($this->chart_type_id);
        }

        return $this->chart_type;
    }

    /**
     * Retrieves the rows that make up this chart release.
     *
     * @return array The chart rows.
     */
    public function getChartRows()
    {
        $chart_rows = [];
        foreach ($this->chart_row_ids as $chart_row_id) {
            $chart_rows[] = MyRadio_ChartRow::getInstance($chart_row_id, $this);
        }

        return $chart_rows;
    }

    /**
     * Sets the chart rows that make up this chart release.
     *
     * @param $chart_rows array  An array of trackids in position order.
     *
     * @return none.
     */
    public function setChartRows($chart_rows)
    {
        $old_rows = $this->getChartRows();
        if (empty($old_rows)) {
            foreach ($chart_rows as $i => $row) {
                MyRadio_ChartRow::create(
                    [
                          'chart_release_id' => $this->getID(),
                          'position' => $i + 1,
                          'trackid' => $row,
                    ]
                );
            }
        } else {
            foreach ($chart_rows as $i => $row) {
                if ($old_rows[$i]->getTrackID() !== $row) {
                    $old_rows[$i]->setTrackID($row);
                }
            }
        }
    }

    /**
     * Creates a new chart release in the database.
     *
     * @param  $data array  An array of data to populate the row with.
     *                     Must contain 'chart_type_id' and 'submitted_time'.
     *
     * @return The chart release with the given ID.
     */
    public static function create($data)
    {
        $r = self::$db->fetchColumn(
            self::INSERT_SQL,
            [
                intval($data['chart_type_id']),
                date('%c', intval($data['submitted_time'])), // Expecting UNIX timestamp
            ],
            true
        );

        return self::getInstance($r[0]);
    }

    /**
     * Sets this chart release's release time.
     *
     * @param int $release_time The new time, as a UNIX timestamp.
     *
     * @return MyRadio_ChartRelease This object, for method chaining.
     */
    public function setReleaseTime($release_time)
    {
        $this->release_time = strtotime($release_time);

        return $this->setDB(self::SET_RELEASE_TIME_SQL, date('c', $release_time));
    }

    /**
     * Sets this chart release's type ID.
     *
     * @param int $chart_type_id The new ID.
     *
     * @return MyRadio_ChartRelease This object, for method chaining.
     */
    public function setChartTypeID($chart_type_id)
    {
        $this->chart_type_id = intval($chart_type_id);

        return $this->setDB(self::SET_CHART_TYPE_ID_SQL, intval($chart_type_id));
    }

    /**
     * Sets a property on the database representation of this chart release.
     *
     * @param string $sql The SQL to use for setting this property.
     * @param $value  The value of the property to set on this chart release.
     *
     * @return MyRadio_ChartRelease This object, for method chaining.
     */
    private function setDB($sql, $value)
    {
        self::$db->query($sql, [$value, $this->getID()]);

        return $this;
    }

    public static function getForm()
    {
        $types = MyRadio_ChartType::getAll();
        $type_select = [['text' => 'Please select...', 'disabled' => true]];
        foreach ($types as $type) {
            $type_select[] = [
                'value' => $type->getID(),
                'text' => $type->getDescription(),
            ];
        }

        $form = (
            new MyRadioForm(
                'charts_editchartrelease',
                'Charts',
                'editChartRelease',
                ['title' => 'Create Chart Release']
            )
        )->addField(
            new MyRadioFormField(
                'chart_type_id',
                MyRadioFormField::TYPE_SELECT,
                [
                    'label' => 'Chart Type',
                    'explanation' => 'The type of chart.',
                    'options' => $type_select,
                ]
            )
        )->addField(
            new MyRadioFormField(
                'submitted_time',
                MyRadioFormField::TYPE_DATE,
                [
                    'label' => 'Release Date',
                    'explanation' => 'The date on which the chart is released.',
                ]
            )
        )->addField(
            new MyRadioFormField(
                'tracks',
                MyRadioFormField::TYPE_TABULARSET,
                array(
                    'options' => array(
                        new MyRadioFormField(
                            'track',
                            MyRadioFormField::TYPE_TRACK,
                            [
                                'label' => 'Tracks',
                            ]
                        ),
                    ),
                )
            )
        );

        return $form;
    }

    public function getEditForm()
    {
        return self::getForm()
            ->setTitle('Edit Chart Release')
            ->editMode(
                $this->getID(),
                [
                    'chart_type_id' => $this->getChartTypeID(),
                    'submitted_time' => CoreUtils::happyTime($this->getReleaseTime(), false),
                    'tracks.track' => array_map(
                        function ($chartRow) {
                            return $chartRow->getTrack();
                        },
                        $this->getChartRows()
                    ),
                ]
            );
    }

    /**
     * Converts this chart release to a table data source.
     *
     * @return array The object as a data source.
     */
    public function toDataSource()
    {
        return [
            'type' => $this->getChartType()->getDescription(),
            'date' => strftime('%c', $this->getReleaseTime()),
            'editlink' => [
                'display' => 'icon',
                'value' => 'pencil',
                'title' => 'Edit Chart Release',
                'url' => URLUtils::makeURL(
                    'Charts',
                    'editChartRelease',
                    ['chart_release_id' => $this->getID()]
                ),
            ],
        ];
    }
}
