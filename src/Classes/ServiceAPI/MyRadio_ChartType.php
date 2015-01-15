<?php
/**
 * Provides the MyRadio_ChartType class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\ServiceAPI;

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\MyRadio\MyRadioForm;
use \MyRadio\MyRadio\MyRadioFormField;

/**
 * The ChartType class fetches information about types of chart.
 * @version 20130426
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Charts
 * @uses \Database
 */
class MyRadio_ChartType extends MyRadio_Type
{
    /**
     * The singleton store for ChartType objects
     * @var MyRadio_ChartType[]
     */
    private static $chart_types = [];

    /**
     * The numeric ID of the chart type.
     * @var Int
     */
    private $chart_type_id;

    /**
     * The list of IDs of MyRadio_ChartReleases for this chart type.
     * @var Int
     */
    private $chart_release_ids;

    /**
     * Constructs a new MyRadio_ChartType from the database.
     *
     * You should generally use MyRadio_ChartType::getInstance instead.
     *
     * @param $chart_type_id  The numeric ID of the chart type.
     *
     * @return The chart type with the given ID.
     */
    protected function __construct($chart_type_id)
    {
        $this->chart_type_id = $chart_type_id;

        $chart_type_data = self::$db->fetchOne(
            'SELECT *
             FROM music.chart_type
             WHERE chart_type_id = $1;',
            [$chart_type_id]
        );
        if (empty($chart_type_data)) {
            throw new MyRadioException('The specified Chart Type does not seem to exist.');

            return;
        }

        parent::constructType($chart_type_data['name'], $chart_type_data['description']);

        $this->chart_release_ids = self::$db->fetchColumn(
            'SELECT chart_release_id
             FROM music.chart_release
             WHERE chart_type_id = $1
             ORDER BY submitted DESC;',
            [$chart_type_id]
        );
    }

    /**
     * Retrieves the MyRadio_ChartType with the given numeric ID.
     *
     * @param $chart_type_id  The numeric ID of the chart type.
     *
     * @return The chart type with the given ID.
     */
    public static function getInstance($chart_type_id = -1)
    {
        self::__wakeup();

        if (!is_numeric($chart_type_id)) {
            throw new MyRadioException(
                'Invalid Chart Type ID!',
                MyRadioException::FATAL
            );
        }

        if (!isset(self::$chart_types[$chart_type_id])) {
            self::$chart_types[$chart_type_id] = new self($chart_type_id);
        }

        return self::$chart_types[$chart_type_id];
    }

    /**
     * Retrieves all current chart types.
     *
     * @return array An array of all active chart types.
     */
    public function getAll()
    {
        $chart_type_ids = self::$db->fetchColumn(
            'SELECT chart_type_id
             FROM music.chart_type
             ORDER BY chart_type_id ASC;',
            []
        );
        $chart_types = [];
        foreach ($chart_type_ids as $chart_type_id) {
            $chart_types[] = self::getInstance($chart_type_id);
        }

        return $chart_types;
    }

    /**
     * Retrieves the unique ID of this chart type.
     *
     * @return The chart type ID.
     */
    public function getID()
    {
        return $this->chart_type_id;
    }

    /**
     * Retrieves the number of releases made under this chart type.
     *
     * @return int The release count.
     */
    public function getNumberOfReleases()
    {
        return sizeof($this->chart_release_ids);
    }

    /**
     * Retrieves the releases made under this chart type.
     *
     * @return array The chart releases.
     */
    public function getReleases()
    {
        $chart_releases = [];
        foreach ($this->chart_release_ids as $chart_release_id) {
            $chart_releases[] = MyRadio_ChartRelease::getInstance($chart_release_id, $this);
        }

        return $chart_releases;
    }

    /**
     * Sets the name of this chart type.
     *
     * @param string $name The new name of the chart type.
     *
     * @return This object, for method chaining.
     */
    public function setName($name)
    {
        if (empty($name)) {
            throw new MyRadioException('Chart type name must not be empty!');
        }

        $this->name = $name;
        self::$db->query(
            'UPDATE music.chart_type
             SET name = $1
             WHERE chart_type_id = $2;',
            [$name, $this->getID()]
        );

        return $this;
    }

    /**
     * Sets the description of this chart type.
     *
     * @param string $description The new description of the chart type.
     *
     * @return This object, for method chaining.
     */
    public function setDescription($description)
    {
        if (empty($description)) {
            throw new MyRadioException('Chart type description must not be empty!');
        }

        $this->description = $description;
        self::$db->query(
            'UPDATE music.chart_type
             SET description = $1
             WHERE chart_type_id = $2;',
            [$description, $this->getID()]
        );

        return $this;
    }

    public static function getForm()
    {
        $form = (
            new MyRadioForm(
                'charts_editcharttype',
                'Charts',
                'editChartType',
                ['title' => 'Edit Chart Type']
            )
        )->addField(
            new MyRadioFormField(
                'name',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Identifier',
                    'explanation' => 'What the chart will be referred to in the website code.'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'description',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Name',
                    'explanation' => 'What the chart will be called on the website itself.'
                ]
            )
        );

        return $form;
    }

    public function getEditForm()
    {
        return self::getForm()
            ->editMode(
                $this->getID(),
                [
                    'name' => $this->getName(),
                    'description' => $this->getDescription()
                ]
            );
    }

    /**
     * Converts this chart type to a table data source.
     *
     * @return array The object as a data source.
     */
    public function toDataSource()
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'releases' => [
                'display' => 'text',
                'value' => $this->getNumberOfReleases(),
                'title' => 'Click to see releases for this chart type.',
                'url' => URLUtils::makeURL(
                    'Charts',
                    'listChartReleases',
                    ['chart_type_id' => $this->getID()]
                )
            ],
            'editlink' => [
                'display' => 'icon',
                'value' => 'script',
                'title' => 'Edit Chart Type',
                'url' => URLUtils::makeURL(
                    'Charts',
                    'editChartType',
                    ['chart_type_id' => $this->getID()]
                )
            ],
        ];
    }
}
