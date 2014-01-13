<?php

/**
 * This file provides the MyRadio_Quote class for MyRadio
 * @package MyRadio_Core
 */

/**
 * A quote in the radio station Quotes Database.
 * 
 * @version 20131020
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Core
 * @uses \Database
 */
class MyRadio_Quote extends ServiceAPI {
  /**
   * The quote ID.
   * @var int
   */
  private $id;

  /**
   * The quote itself.
   * @var string
   */
  private $text;

  /**
   * The member who said the quote.
   * @var User
   */
  private $source;

  /**
   * The date of the quote, as a UNIX timestamp. 
   * @var int
   */
  private $date;


  /**
   * Constructs a new MyRadio_Quote from the database.
   *
   * You should generally use MyRadio_Quote::getInstance instead.
   *
   * @param int $quote_id  The numeric ID of the quote.
   *
   * @return MyRadio_Quote  The quote with the given ID.
   */
  protected function __construct($quote_id) {
    $quote_data = self::$db->fetch_one(
      'SELECT *
       FROM   people.quote
       WHERE  quote_id = $1;',
      [$quote_id]
    );
    if (empty($quote_data)) {
      throw new MyUryException('The specified Quote does not seem to exist.');
      return;
    }

    $this->text = $quote_data['text']
    $this->source = User::getInstance($quote_data['source']);
    $this->date = strtotime($quote_data['date']);
  }

  /**
   * Retrieves all current quotes.
   *
   * @return array  An array of all active quotes.
   */
  public function getAll() {
    $chart_type_ids = self::$db->fetch_column(
      'SELECT quote_id
       FROM people.quote
       ORDER BY date DESC;',
      []
    );
    return array_map(self::getInstance, $chart_type_ids);
  }


  /**
   * @return int The quote ID.
   */
  public function getID() {
    return $this->id;
  }

  /**
   * @return string The quote text.
   */
  public function getText() {
    return $this->$text;
  }

  /**
   * @return User The quote source.
   */
  public function getSource() {
    return $this->$source;
  }

  /**
   * @return int The quote time, as a UNIX timestamp.
   */
  public function getDate() {
    return $this->$date;
  }


  /**
   * Creates a new quote in the database.
   *
   * @param $data array  An array of data to populate the row with.
   *                     Must contain 'text', 'source' and 'date'.
   * @return nothing.
   */
  public function create($data) {
    self::$db->query(
      'INSERT INTO people.quote(text, source, date)
       VALUES ($1, $2, $3);',
      [
        $text,
        $data['source']->getID(),  
        date('%c', intval($data['date'])) // Expecting UNIX timestamp
      ],
      true
    );
  }


  /**
   * Sets this chart's quote text.
   * @param string $text  the quote text.
   * @return MyRadio_Quote  This object, for method chaining.
   */
  public function setText($text) {
    self::$db->query(
      'UPDATE people.quote
       SET    text     = $1
       WHERE  quote_id = $2;',
      [$text, $this->getID()]
    );
    return $this;
  }

  /**
   * Sets this chart's quote source.
   * @param User $source  the quote source.
   * @return MyRadio_Quote  This object, for method chaining.
   */
  public function setSource($source) {
    self::$db->query(
      'UPDATE people.quote
       SET    source   = $1
       WHERE  quote_id = $2;',
      [$source->getID(), $this->getID()]
    );
    return $this;
  }

  /**
   * Sets this chart's quote time.
   * @param int|string $date  the date, as a UNIX timestamp or date string.
   * @return MyRadio_Quote  This object, for method chaining.
   */
  public function setDate($date) {
    self::$db->query(
      'UPDATE people.quote
       SET    date     = $1
       WHERE  quote_id = $2;',
      [strtotime($date), $this->getID()]
    );
    return $this;
  }


  /**
   * Converts this quote to a table data source.
   *
   * @return array  The object as a data source.
   */
  public function toDataSource() {
    return [
      'source' => $this->getSource(),
      'text' => $this->getText(),
      'date' => strftime('%c', $this->getDate()),
      'editlink' => [
        'display' => 'icon',
        'value' => 'script',
        'title' => 'Edit Quote',
        'url' => CoreUtils::makeURL(
          'Charts',
          'editQuote',
          ['quote_id' => $this->getID()]
        )
      ],
    ];
  }
}

?>
