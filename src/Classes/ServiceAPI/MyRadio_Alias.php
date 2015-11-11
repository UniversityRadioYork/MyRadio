<?php
/**
 * Provides the Alias class for MyRadio
 * @package MyRadio_Mail
 */

namespace MyRadio\ServiceAPI;

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;

/**
 * The Alias class is used to do stuff with Aliases in URY's mail system.
 *
 * @package MyRadio_Mail
 * @uses    \Database
 */
class MyRadio_Alias extends ServiceAPI
{
    /**
     * The ID of the Alias
     * @var int
     */
    private $alias_id;

    /**
     * The source of the alias
     * If this is an alias from foo@ury.org.uk to bar@ury.org.uk, this value is
     * 'foo'
     * @var String
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
        $result = self::$db->fetchOne(
            'SELECT source, '
            . '(SELECT array(SELECT destination FROM mail.alias_text '
            . '  WHERE alias_id=$1)) AS dtext, '
            . '(SELECT array(SELECT destination FROM mail.alias_officer '
            . '  WHERE alias_id=$1)) AS dofficer, '
            . '(SELECT array(SELECT destination FROM mail.alias_member '
            . '  WHERE alias_id=$1)) AS dmember, '
            . '(SELECT array(SELECT destination FROM mail.alias_list '
            . '  WHERE alias_id=$1)) AS dlist '
            . 'FROM mail.alias WHERE alias_id=$1',
            [$id]
        );
        if (empty($result)) {
            throw new MyRadioException('Alias '.$id.' does not exist!', 404);
        } else {
            $this->alias_id = (int) $id;
            $this->source = $result['source'];

            foreach (self::$db->decodeArray($result['dtext']) as $text) {
                $this->destinations[] = [
                    'type' => 'text',
                    'value' => $text
                ];
            }

            foreach (self::$db->decodeArray($result['dofficer']) as $officer) {
                $this->destinations[] = [
                    'type' => 'officer',
                    'value' => MyRadio_Officer::getInstance($officer)
                ];
            }

            foreach (self::$db->decodeArray($result['dmember']) as $member) {
                $this->destinations[] = [
                    'type' => 'member',
                    'value' => MyRadio_User::getInstance($member)
                ];
            }

            foreach (self::$db->decodeArray($result['dlist']) as $list) {
                $this->destinations[] = [
                    'type' => 'list',
                    'value' => MyRadio_List::getInstance($list)
                ];
            }
        }
    }

    /**
     * Returns all the Aliases available.
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
     * Get the ID fo this Alias
     * @return int
     */
    public function getID()
    {
        return $this->alias_id;
    }

    /**
     * Returns the string prefix of the Alias.
     *
     * @return String
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

    /**
     * Returns data about the Alias for the API.
     *
     * @param  Array $mixins
     * @return Array
     */
    public function toDataSource($mixins = [])
    {
        $data = [
            'alias_id' => $this->getID(),
            'source' => $this->getSource(),
            'destinations' => CoreUtils::dataSourceParser($this->getDestinations(), $mixins)
        ];

        return $data;
    }
}
