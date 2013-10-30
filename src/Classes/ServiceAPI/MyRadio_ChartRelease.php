<?php
/**
 * Provides the MyRadio_ChartRelease class for MyRadio
 * @package MyRadio_Charts
 */

/**
 * The ChartRelease class fetches information about chart releases.
 *
 * A chart release is a chart for a given week, and is associated with a chart
 * and an arbitrary number of rows (traditionally 10).
 *
 * @version 20130426
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Charts
 * @uses \Database
 */
class MyRadio_ChartRelease extends ServiceAPI {
  /**
   * The singleton store for ChartRelease objects
   * @var MyRadio_ChartRelease[]
   */
  private static $chart_releases = [];

  /**
   * The chart type this chart release was released under.
   * @var MyRadio_ChartType
   */
  private $chart_type;

  /**
   * The numeric ID of the chart type.
   * @var Int
   */
  private $chart_type_id;

  /**
   * The numeric ID of the chart release.
   * @var Int
   */
  private $chart_release_id;

  /**
   * The UNIX timestamp, if any, on which this chart release was released.
   * @var Int
   */
  private $release_time;

  /**
   * The list of IDs of MyRadio_ChartRows for this chart release.
   * @var Int[]
   */
  private $chart_row_ids;

  /**
   * Constructs a new MyRadio_ChartRelease from the database.
   *
   * You should generally use MyRadio_ChartRelease::getInstance instead.
   *
   * @param $chart_release_id  The numeric ID of the chart release.
   * @param $chart_type  The parent chart type, if any.
   *
   * @return The chart type with the given ID.
   */
  protected function __construct($chart_release_id, $chart_type=null) {
    $this->chart_release_id = $chart_release_id;
    $this->chart_type = $chart_type;

    $chart_release_data = self::$db->fetch_one(
      'SELECT *
       FROM music.chart_release
       WHERE chart_release_id = $1;',
      [$chart_release_id]
    );
    if (empty($chart_release_data)) {
      throw new MyRadioException('The specified Chart Release does not seem to exist.');
      return;
    }

    $this->release_time = strtotime($chart_release_data['submitted']);
    $this->chart_type_id = $chart_release_data['chart_type_id'];

    $this->chart_row_ids = self::$db->fetch_column(
      'SELECT chart_row_id
       FROM music.chart_row
       WHERE chart_release_id = $1
       ORDER BY position ASC;',
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
  public static function getInstance($chart_release_id=-1, $chart_type=null) {
    self::__wakeup();

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
   * Retrieves the ID of the (first) chart released on the given date on the given chart type.
   *
   * This is mainly useful for finding a newly created chart type's ID.
   *
   * @param int $release_time  The release time, as a UNIX timestamp.
   * @param int $chart_type_id  The ID of the chart type to search in.
   * @return int  The first chart released on the given time for
   *                             the given type.
   */
  public function findReleaseIDOn($release_time, $chart_type_id) {
    return array_pop(
      self::$db->fetch_column(
        'SELECT chart_release_id
         FROM music.chart_release
         WHERE chart_type_id = $1
         AND submitted = $2
         LIMIT 1;',
        [
          $chart_type_id,
          date('c', $release_time)
        ]
      )
    );
  }
 
  /**
   * Retrieves the time of release of this chart release.
   *
   * @return  the submission time as a UNIX timestamp.
   */
  public function getReleaseTime() {
    return $this->release_time;
  }

  /**
   * Retrieves the unique ID of this chart release.
   *
   * @return The chart release ID.
   */
  public function getID() {
    return $this->chart_release_id;
  }

  /**
   * Retrieves the unique ID of this chart release's type.
   *
   * @return The chart type ID.
   */
  public function getChartTypeID() {
    return $this->chart_type_id;
  }

  /**
   * Retrieves the type this chart release falls under.
   *
   * @return MyRadio_ChartType The chart type object.
   */
  public function getChartType() {
    if ($this->chart_type === null) {
      $this->chart_type = MyRadio_ChartType::getInstance($this->chart_type_id);
    }
    return $this->chart_type;
  }

  /**
   * Retrieves the rows that make up this chart release.
   *
   * @return The chart rows.
   */
  public function getChartRows() {
    $chart_rows = [];
    foreach ($this->chart_row_ids as $chart_row_id) {
      $chart_rows[] = MyRadio_ChartRow::getInstance($chart_row_id, $this);
    }
    return $chart_rows;
  }

  /**
   * Creates a new chart release in the database.
   *
   * @param $data array  An array of data to populate the row with.
   *                     Must contain 'chart_type_id' and 'submitted_time'.
   * @return nothing.
   */
  public function create($data) {
    self::$db->query(
      'INSERT INTO music.chart_release(chart_type_id, submitted)
       VALUES ($1, $2);',
      [
        intval($data['chart_type_id']),  
        date('%c', intval($data['submitted_time'])) // Expecting UNIX timestamp
      ],
      true
    );
  }

  /**
   * Sets this chart release's release time.
   *
   * @param int $release_time  The new time, as a UNIX timestamp.
   *
   * @return This object, for method chaining.
   */
  public function setReleaseTime($release_time) {
    $this->release_time = strtotime($release_time);

    self::$db->query(
      'UPDATE music.chart_release
       SET submitted = $1
       WHERE chart_release_id = $2;',
      [date('c', $release_time), $this->getID()]
    );

    return $this;
  }

  /**
   * Sets this chart release's type ID.
   *
   * @param int $chart_type_id  The new ID.
   *
   * @return This object, for method chaining.
   */
  public function setChartTypeID($chart_type_id) {
    $this->chart_type_id = intval($chart_type_id);

    self::$db->query(
      'UPDATE music.chart_release
       SET chart_type_id = $1
       WHERE chart_release_id = $2;',
      [$chart_type_id, $this->getID()]
    );

    return $this;
  }

  /**
   * Converts this chart release to a table data source.
   *
   * @return array  The object as a data source.
   */
  public function toDataSource() {
    return [
      'type' => $this->getChartType()->getDescription(),
      'date' => strftime('%c', $this->getReleaseTime()),
      'editlink' => [
        'display' => 'icon',
        'value' => 'script',
        'title' => 'Edit Chart Release',
        'url' => CoreUtils::makeURL(
          'Charts',
          'editChartRelease',
          ['chart_release_id' => $this->getID()]
        )
      ],
    ];
  }
}
?>
