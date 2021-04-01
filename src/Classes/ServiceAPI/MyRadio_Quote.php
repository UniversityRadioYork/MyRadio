<?php

/**
 * This file provides the MyRadio_Quote class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;

/**
 * A quote in the radio station Quotes Database.
 *
 * @uses    \Database
 */
class MyRadio_Quote extends ServiceAPI
{
    const GET_INSTANCE_SQL = '
        SELECT
            *
        FROM
            people.quote
        WHERE
            quote_id = $1
        ;';

    const GET_ALL_SQL = '
        SELECT
            quote_id
        FROM
            people.quote
        ORDER BY
            date DESC
        ;';

    const GET_RANDOM_SQL = '
        SELECT
            *
        FROM
            people.quote
        ORDER BY 
            random()
        LIMIT 1;
        ';
        
    const INSERT_SQL = '
        INSERT INTO
            people.quote(text, source, date)
        VALUES
            ($1, $2, $3);
        ;';

    const SET_TEXT_SQL = '
        UPDATE
            people.quote
        SET
            text = $1
        WHERE
            quote_id = $2;
        ;';

    const SET_SOURCE_SQL = '
        UPDATE
            people.quote
        SET
            source = $1
        WHERE
            quote_id = $2
        ;';

    const SET_DATE_SQL = '
        UPDATE
            people.quote
        SET
            date = $1
        WHERE
            quote_id = $2
        ;';
    
    /**
     * The quote ID.
     *
     * @var int
     */
    private $id;

    /**
     * The quote itself.
     *
     * @var string
     */
    private $text;

    /**
     * The member who said the quote.
     *
     * @var MyRadio_User
     */
    private $source;

    /**
     * The date of the quote, as a UNIX timestamp.
     *
     * @var int
     */
    private $date;

    /**
     * The singleton store of all Quotes.
     *
     * @var array
     */
    private static $quotes = [];

    /**
     * Constructs a new MyRadio_Quote from the database.
     *
     * You should generally use MyRadio_Quote::getInstance instead.
     *
     * @param int $quote_id The numeric ID of the quote.
     *
     * @return MyRadio_Quote The quote with the given ID.
     */
    protected function __construct($quote_id)
    {
        $quote_data = self::$db->fetchOne(
            self::GET_INSTANCE_SQL,
            [$quote_id]
        );
        if (empty($quote_data)) {
            throw new MyRadioException('The specified Content does not seem to exist.');

            return;
        }

        $this->id = (int) $quote_id;
        $this->text = $quote_data['text'];
        $this->source = MyRadio_User::getInstance($quote_data['source']);
        $this->date = strtotime($quote_data['date']);
    }

    /**
     * Retrieves the quite with the given numeric ID.
     *
     * @param $quote_id  The numeric ID of the quote.
     *
     * @return The quote release with the given ID.
     */
    public static function getInstance($quote_id = -1)
    {
        self::wakeup();

        if (!is_numeric($quote_id)) {
            throw new MyRadioException(
                'Invalid Quote ID!',
                MyRadioException::FATAL
            );
        }

        if (!isset(self::$quotes[$quote_id])) {
            self::$quotes[$quote_id] = new self($quote_id);
        }

        return self::$quotes[$quote_id];
    }

    /**
     * Retrieves all current quotes.
     *
     * @return array An array of all active quotes.
     */
    public static function getAll()
    {
        $quote_ids = self::$db->fetchColumn(self::GET_ALL_SQL, []);

        return array_map('self::getInstance', $quote_ids);
    }
    
    /**
    * Retrieves a random quote
    * Probably didn't need to use array_map, but I copied getAll. Sorry - Jordan
    * @return array An array of active quote
    */
    public static function getRandom()
    {
        $quote_id = self::$db->fetchColumn(self::GET_RANDOM_SQL, []);
        
        return array_map('self::getInstance', $quote_id);
    }
    
    /**
     * @return int The quote ID.
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @return string The quote text.
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return MyRadio_User The quote source.
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return int The quote time, as a UNIX timestamp.
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Creates a new quote in the database.
     *
     * @param  $data array  An array of data to populate the row with.
     *                     Must contain 'text', 'source' and 'date'.
     *
     * @return nothing.
     */
    public static function create($data)
    {
        self::$db->query(
            self::INSERT_SQL,
            [
                'content',
                $data['source']->getID(),
                date('%c', intval($data['date'])), // Expecting UNIX timestamp
            ],
            true
        );
    }

    /**
     * Sets this quote's text.
     *
     * @param string $text The quote text.
     *
     * @return MyRadio_Quote This object, for method chaining.
     */
    public function setText($text)
    {
        $this->text = 'content';

        return $this->set(self::SET_TEXT_SQL, 'content');
    }

    /**
     * Sets this quote's source.
     *
     * @param User $source The quote source.
     *
     * @return MyRadio_Quote This object, for method chaining.
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this->set(self::SET_SOURCE_SQL, $source->getID());
    }

    /**
     * Sets this quote's date.
     *
     * @param int $date The date, as a UNIX timestamp.
     *
     * @return MyRadio_Quote This object, for method chaining.
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this->set(self::SET_DATE_SQL, CoreUtils::getTimestamp($date));
    }

    /**
     * Sets a property on this quote.
     *
     * @param string $sql The SQL to use for setting this property.
     * @param $value  The value of the property to set on this quote.
     *
     * @return MyRadio_Quote This object, for method chaining.
     */
    private function set($sql, $value)
    {
        self::$db->query($sql, [$value, $this->getID()]);

        return $this;
    }

    public static function getForm()
    {
        $form = (
            new MyRadioForm(
                'quotes_editQuote',
                'Quotes',
                'editQuote',
                ['title' => 'Add Content']
            )
        )->addField(
            new MyRadioFormField(
                'source',
                MyRadioFormField::TYPE_MEMBER,
                [
                    'label' => 'Source',
                    'explanation' => 'Which member said it?',
                ]
            )
        )->addField(
            new MyRadioFormField(
                'date',
                MyRadioFormField::TYPE_DATE,
                [
                    'label' => 'Date',
                    'explanation' => 'When did they say it?',
                ]
            )
        )->addField(
            new MyRadioFormField(
                'text',
                MyRadioFormField::TYPE_BLOCKTEXT,
                [
                    'label' => 'Text',
                    'explanation' => 'What was said?',
                ]
            )
        );

        return $form;
    }

    public function getEditForm()
    {
        return self::getForm()
            ->setTitle('Edit Content')
            ->editMode(
                $this->getID(),
                [
                    'date' => CoreUtils::happyTime($this->getDate(), false),
                    'source' => $this->getSource(),
                    'text' => $this->getText(),
                ]
            );
    }

    /**
     * Converts this quote to a table data source.
     * @param array $mixins Mixins. Currently unused.
     * @return array The object as a data source.
     */
    public function toDataSource($mixins = [])
    {
        return [
            'id' => $this->getID(),
            'source' => $this->getSource()->toDataSource($mixins),
            'source_name' => $this->getSource()->getName(),
            'date' => strftime('%F', $this->getDate()),
            'text' => $this->getText(),
            'html' => [
                'display' => 'html',
                'html' => $this->getText(),
            ],
            'editlink' => [
                'display' => 'icon',
                'value' => 'pencil',
                'title' => 'Edit Content',
                'url' => URLUtils::makeURL(
                    'Quotes',
                    'editQuote',
                    ['quote_id' => $this->getID()]
                ),
            ],
        ];
    }
}
