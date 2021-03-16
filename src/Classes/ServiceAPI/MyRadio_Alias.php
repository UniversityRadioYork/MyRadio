<?php
/**
 * Provides the Alias class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;

/**
 * The Alias class is used to do stuff with Aliases in URY's mail system.
 *
 * @uses    \Database
 */
class MyRadio_Alias extends ServiceAPI
{
    /**
     * The ID of the Alias.
     *
     * @var int
     */
    private $alias_id;

    /**
     * The source of the alias
     * If this is an alias from foo@ury.org.uk to bar@ury.org.uk, this value is
     * 'foo'.
     *
     * @var string
     */
    private $source;

    /**
     * An array of Lists, Users, Officers and text destinations for the Alias.
     *
     * Format:<br>
     * {{type: 'text', value: 'dave.tracz'}, ...}
     *
     * @var mixed[]
     */
    private $destinations = [];

    protected function __construct($id)
    {
        $this->alias_id = (int) $id;
        $this->updateInternal();
    }

    private function updateInternal()
    {
        $this->source = '';
        $this->destinations = [];

        $result = self::$db->fetchOne(
            'SELECT source, (
                SELECT array_to_json(array(
                    SELECT destination FROM mail.alias_text WHERE alias_id=$1
                ))
            ) AS dtext, (
                SELECT array_to_json(array(
                    SELECT destination FROM mail.alias_officer WHERE alias_id=$1
                ))
            ) AS dofficer, (
                SELECT array_to_json(array(
                    SELECT destination FROM mail.alias_member WHERE alias_id=$1
                ))
            ) AS dmember, (
                SELECT array_to_json(array(
                    SELECT destination FROM mail.alias_list WHERE alias_id=$1
                ))
            ) AS dlist
            FROM mail.alias WHERE alias_id=$1',
            [$this->alias_id]
        );
        if (empty($result)) {
            throw new MyRadioException('Alias '.$this->alias_id.' does not exist!', 404);
        } else {
            $this->source = $result['source'];

            foreach (json_decode($result['dtext']) as $text) {
                $this->destinations[] = [
                    'type' => 'text',
                    'value' => $text,
                ];
            }

            foreach (json_decode($result['dofficer']) as $officer) {
                $this->destinations[] = [
                    'type' => 'officer',
                    'value' => MyRadio_Officer::getInstance($officer),
                ];
            }

            foreach (json_decode($result['dmember']) as $member) {
                $this->destinations[] = [
                    'type' => 'member',
                    'value' => MyRadio_User::getInstance($member),
                ];
            }

            foreach (json_decode($result['dlist']) as $list) {
                $this->destinations[] = [
                    'type' => 'list',
                    'value' => MyRadio_List::getInstance($list),
                ];
            }
        }
    }

    /**
     * Returns all the Aliases available.
     *
     * @return array
     */
    public static function getAllAliases()
    {
        return self::resultSetToObjArray(
            self::$db->fetchColumn(
                'SELECT alias_id FROM mail.alias'
            )
        );
    }

    /**
     * Get the ID fo this Alias.
     *
     * @return int
     */
    public function getID()
    {
        return $this->alias_id;
    }

    /**
     * Returns the string prefix of the Alias.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Returns what the Alias maps to.
     *
     * Format:<br>
     * {{type: 'text', value: string/Officer/Member}, ...}
     *
     * @return mixed[]
     */
    public function getDestinations()
    {
        return $this->destinations;
    }

    private const ALIAS_TYPE_TABLES = [
        'text' => 'alias_text',
        'member' => 'alias_member',
        'officer' => 'alias_officer',
        'list' => 'alias_list'
    ];

    /**
     * Adds a destination to this alias.
     * @param $type string
     * @param $value string|int
     * @return $this
     */
    public function addDestination(string $type, $value)
    {
        if (!isset(self::ALIAS_TYPE_TABLES[$type])) {
            throw new MyRadioException("Unknown alias destination type $type");
        }
        $table = 'mail.' . self::ALIAS_TYPE_TABLES[$type];
        self::$db->query(
            "INSERT INTO $table (alias_id, destination)
            VALUES ($1, $2)",
            [$this->getID(), $value]
        );
        $this->updateInternal();
        $this->updateCacheObject();
        return $this;
    }

    /**
     * Remove a destination from this alias.
     * @param string $type
     * @param $value
     * @return $this
     */
    public function removeDestination(string $type, $value)
    {
        if (!isset(self::ALIAS_TYPE_TABLES[$type])) {
            throw new MyRadioException("Unknown alias destination type $type");
        }
        $table = 'mail.' . self::ALIAS_TYPE_TABLES[$type];
        self::$db->query(
            "DELETE FROM $table
            WHERE alias_id = $1 AND destination = $2",
            [$this->getID(), $value]
        );
        $this->updateInternal();
        $this->updateCacheObject();
        return $this;
    }

    /**
     * Delete this alias.
     */
    public function delete()
    {
        self::$db->query('BEGIN', []);
        foreach (self::ALIAS_TYPE_TABLES as $_ => $table) {
            self::$db->query(
                "DELETE FROM mail.${table} WHERE alias_id = $1",
                [$this->alias_id]
            );
        }
        self::$db->query(
            'DELETE FROM mail.alias WHERE alias_id = $1',
            [$this->alias_id]
        );
        self::$db->query('COMMIT', []);
        self::$cache->purge();
    }

    /**
     * Creates a new alias, returning the newly created alias.
     *
     * `$destinations` should be an array of arrays, where the inner arrays are in the format:
     * [
     *  'type' => 'text|officer|member|list',
     *  'value' => string | int (string for text and list, int for member and officer)
     * ]
     *
     * @param $source string the source name (<source>@<Config::$email_domain>)
     * @param array $destinations where the alias should point
     * @return self
     */
    public static function create(string $source, $destinations = [])
    {
        $result = self::$db->fetchOne(
            'INSERT INTO mail.alias (source) VALUES ($1) RETURNING alias_id',
            [$source]
        );
        return self::getInstance($result['alias_id']);
    }

    /**
     * Returns data about the Alias for the API.
     * @param array $mixins
     * @return array
     */
    public function toDataSource($mixins = [])
    {
        $data = [
            'alias_id' => $this->getID(),
            'source' => $this->getSource(),
            'destinations' => CoreUtils::dataSourceParser($this->getDestinations()),
        ];

        return $data;
    }
}
