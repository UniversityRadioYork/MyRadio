<?php

namespace MyRadio\MyRadio;

use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_Album;
use MyRadio\ServiceAPI\MyRadio_Track;
use MyRadio\ServiceAPI\MyRadio_User;

/**
 * This file provides the MyRadioFormField class for MyRadio.
 */

/**
 * An input of some description that will be rendered in a form
 * A collection of these is automatically created when building a MyRadioForm.
 */
class MyRadioFormField
{
    /**
     * The constant used to specify this MyRadioFormField should be a standard text field.
     *
     * A text field can take the following custom options:
     *
     * minlength: The minimum number of characters the user must enter for this to be valid input
     *
     * maxlength: The maximum number of characters the user can enter for this to be valid input
     *
     * placeholder: Placeholder text that is cleared when the input takes focus.
     */
    const TYPE_TEXT = 0x00;
    /**
     * The constant used to specify this MyRadioFormField should be a standard number field.
     *
     * A number field can take the following custom options:
     *
     * min: The lowest number the user must enter for this to be valid input
     *
     * max: The highest number the user can enter for this to be valid input
     */
    const TYPE_NUMBER = 0x01;
    /**
     * The constant used to specify this MyRadioFormField must be a text field that validates as an email address.
     *
     * The email field takes no custom options.
     */
    const TYPE_EMAIL = 0x02;
    /**
     * The constant used to specify this MyRadioFormField must be a valid date, and provides a datepicker widget for it.
     *
     * The date field currently takes no custom options.
     *
     * @todo Support for mindate and maxdate
     */
    const TYPE_DATE = 0x03;
    /**
     * The constant used to specify this MyRadioFormField must be a valid date and time, providing a datetime widget for it.
     *
     * The datetime field currently takes no custom options.
     * NOTE: Currently, the TIME aspect must be in 15 minute intervals
     *
     * @todo Support for a custom time interval
     * @todo Support for mindate and maxdate
     * @todo Support for mintime and maxtime
     */
    const TYPE_DATETIME = 0x04;
    /**
     * The constant used to specify this MyRadioFormField must be a valid member, providing a Member autocomplete for it.
     * This actually renders two fields - the visible one the user can enter a name into, and a hidden one that will
     * store the ID once it has been selected.
     *
     * The member field takes the following custom options:
     *
     * membername: Since value will set the hidden integer value, this can be used to set the text value of the visible
     * element when loading a pre-filled form.
     *
     * @todo Support for only displaying this year's members in the search query
     */
    const TYPE_MEMBER = 0x05;
    /**
     * The constant used to specify this MyRadioFormField must be a valid track, providing a Track autocomplete for it.
     * This actually renders two fields - the visible one the user can enter a track into, and a hidden one that will
     * store the ID once it has been selected. The value option takes a MyRadio_Track object.
     *
     * The track field takes the following custom options:
     *
     * trackname: Since value will set the hidden integer value, this can be used to set the text value of the visible
     * element when loading a pre-filled form.
     * digitised: Require that search results are digitised tracks. Default false.
     *
     * @todo Support for filtering to only digitised, clean tracks etc.
     */
    const TYPE_TRACK = 0x06;
    /**
     * The constant used to specify this MyRadioFormField must be a valid artist, providing an Artist autocomplete for it.
     * This actually renders two fields - the visible one the user can enter an artist into, and a hidden one that will
     * store the ID once it has been selected.
     *
     * The artist field takes the following custom options:
     *
     * artistname: Since value will set the hidden integer value, this can be used to set the text value of the visible
     * element when loading a pre-filled form.
     *
     * @todo This currently doesn't work right as the Artists system needs some significant backend changes
     */
    const TYPE_ARTIST = 0x07;
    /**
     * The constant used to specify this MyRadioFormField must be a standard HTML hidden field type.
     *
     * The hidden field takes no custom options.
     */
    const TYPE_HIDDEN = 0x08;
    /**
     * The constant used to specify this MyRadioFormField must be a standard HTML select field.
     *
     * The Custom Options property for this MyRadioFormField type is an Array of items in the select list, each defined as
     * follows:
     *
     * value: The value of the select option.
     *
     * disabled: If true, this option cannot be selected (default false)
     *
     * text: The human-readable value of the option that is displayed in the select dropdown.
     */
    const TYPE_SELECT = 0x09;
    /**
     * The constant used to specify this MyRadioFormField must be a set of standard HTML radio fields.
     *
     * The Custom Options property for this MyRadioFormField type is an Array of items in the Radio list, each defined as
     * follows:
     *
     * value: The value of the radio option.
     *
     * disabled: If true, this option cannot be selected (default false)
     *
     * text: The human-readable value of this option that is displayed next to the radio button
     */
    const TYPE_RADIO = 0x0A;
    /**
     * The constant used to specify this MyRadioFormField must be a check box.
     *
     * This field type does *not* use the value field, due to the way defaults work.
     *
     * The Custom Options this MyRadioFormField uses are:
     *
     * checked: Whether or not this checkbox is checked by default. Default false.
     */
    const TYPE_CHECK = 0x0B;
    /**
     * The constant used to specify this MyRadioFormField must be a select input with the days of the week as options.
     *
     * It returns a number from 0-6, with 0 representing Monday. Value can be used to pre-set a day using these numbers.
     *
     * This field type does not use any Custom Options.
     */
    const TYPE_DAY = 0x0C;
    /**
     * The constant used to specify this MyRadioFormField should be a textarea with rich text input.
     *
     * The following Custom Options are supported by this MyRadioFormField type:
     *
     * minlength: The minimum number of characters the user must enter. This will include inserted HTML tags by the RTE.
     *
     * maxlength: The maximum number of characters the user may enter. This will include inserted HTML tags by the RTE.
     *
     * @todo Support custom # of rows and columns
     */
    const TYPE_BLOCKTEXT = 0x0D;
    /**
     * The constant used to specify this MyRadioFormField should be a text field that only accepts a time input.
     * NOTE: This currently only accepts time entries at 15 minute intervals.
     *
     * @todo Support for custom time intervals
     *
     * This MyRadioFormField type does not support any Custom Options.
     */
    const TYPE_TIME = 0x0E;
    /**
     * The constant used to specify this MyRadioFormField should be a group of checkbox MyRadioFormFields grouped within a
     * a single fieldset. This provides the advantage of Select All and Select None links and a generally more organised
     * feel.
     *
     * The Custom Options field for this MyRadioFormField field type is an Array of MyRadioFormFields of the Checkbox type
     * which are to be rendered within this MyRadioFormField.
     */
    const TYPE_CHECKGRP = 0x0F;
    /**
     * The constant used to specify this MyRadioFormField creates a fieldset.
     * This should be closed with a TYPE_SECTION_CLOSE.
     *
     * This MyRadioFormField type does not support any Custom Options
     *
     * @todo Collapsible?
     */
    const TYPE_SECTION = 0x10;
    /**
     * The constant used to specify this MyRadioFormField should be a container for a set of repeating MyRadioFormFields.
     * By default these render in a tabular layout.
     *
     * The Custom Options field for this MyRadioFormField field type is an Array of MyRadioFormFields of any singular type.
     * This means that CHECKGRP, SECTION and other similar field types are not supported by this MyRadioFormField Type
     * and may have... interesting... results.
     */
    const TYPE_TABULARSET = 0x11;
    /**
     * The constant used to specify this MyRadioFormField should be a file upload.
     *
     * The file field takes the following custom options:
     *
     * progress: If true, will display an upload progress bar.
     */
    const TYPE_FILE = 0x12;
    /**
     * The constant used to specify this MyRadioFormField must be a valid album, providing an Album autocomplete for it.
     * This actually renders two fields - the visible one the user can enter an artist into, and a hidden one that will
     * store the ID once it has been selected.
     *
     * The album field takes the following custom options:
     *
     * albumname: Since value will set the hidden integer value, this can be used to set the text value of the visible
     * element when loading a pre-filled form.
     */
    const TYPE_ALBUM = 0x13;

    /**
     * The constant used to specify this MyRadioFormField should be a checkbox matrix formatted to
     * generate an appointment selector on a standard week.
     *
     * The value of this form field should be a 2D array of 0 or more time ranges, of the format:<br>
     * {day: 1, start_time: 3600, end_time: 7200}<br>
     * Which is Monday, from 1am to 2am (times are seconds since midnight).
     *
     * This field type takes no custom options.
     * This field type does not support repeating or tabular sets.
     * This field type does not support the "required" parameter.
     */
    const TYPE_WEEKSELECT = 0x14;

    /**
     * The constant used to specify this MyRadioFormField should be a password field.
     * This MyRadioFormField type does not support any Custom Options.
     */
    const TYPE_PASSWORD = 0x15;

    /**
     * The constant used to specify this MyRadioFormField closes a fieldset.
     *
     * This MyRadioFormField type does not support any Custom Options
     */
    const TYPE_SECTION_CLOSE = 0x16;

    /**
     * The name/id of the Form Field.
     *
     * @var string
     */
    private $name;

    /**
     * The type of the form field.
     *
     * @var int
     */
    private $type;

    /**
     * Whether input in this field is required.
     *
     * @var bool
     */
    private $required = true;

    /**
     * The label of the field (null = use name).
     *
     * @var string
     */
    private $label = null;

    /**
     * Helpful text explaining the form field.
     *
     * @var string
     */
    private $explanation = '';

    /**
     * Whether the form element should be visible.
     *
     * @var bool
     */
    private $display = true;

    /**
     * Additional classes to add to the field.
     *
     * @var array
     */
    private $classes = [];

    /**
     * For selects, radios and checkboxes only - the options to display.
     *
     * @var 2D Array as defined
     *         {display: 'Value to Display', enabled: true}
     */
    private $options = [];

    /**
     * The value of the form field.
     *
     * @var mixed
     */
    private $value = null;

    /**
     * Whether the field is enabled/disabled by default
     * Actually renders as readonly in most cases.
     *
     * @var bool
     */
    private $enabled = true;

    /**
     * Whether the field is redacted by default.
     *
     * @var bool
     */
    private $redacted = false;

    /**
     * Settings that cannot be altered by the $options parameter.
     *
     * @var array
     */
    private $restricted_attributes = ['restricted_attributes', 'name', 'type'];

    /**
     * Set up a new MyRadio Form Field with the new parameters, returning the new field.
     * This method is only useful practically when the MyRadioFormField is inserted to a MyRadioForm.
     *
     * @param string $name    The name and id of the field, as used in the HTML properties - should be unique to the form '.' IS A RESERVED CHARACTER!
     *                        '.' IS A RESERVED CHARACTER!
     * @param int    $type    The MyRadioFormField Field Type to use. See the constants defined in this class for details
     * @param array  $options A set of additional settings for the MyRadioFormField as follows (all optional):<br> required: Whether the field is required (default true)<br> label: The human-readable name of the field. (default reuses name)<br> explanation: Help text for the MyRadioFormField (default none)<br> display: Whether the MyRadioFormField should be visible when the page loads (default true)<br> classes: An array of additional classes to add to the input field (default empty)<br> options: An array of additional settings that are specific to the field type (default empty)<br> value: The default value of the field when it is rendered (default none)<br> enabled: Whether the field is enabled when the page is loaded (default true)<br> redacted: If true, this field is hidden in debug output (default false)
     *                        required: Whether the field is required (default true)<br>
     *                        label: The human-readable name of the field. (default reuses name)<br>
     *                        explanation: Help text for the MyRadioFormField (default none)<br>
     *                        display: Whether the MyRadioFormField should be visible when the page loads (default true)<br>
     *                        classes: An array of additional classes to add to the input field (default empty)<br>
     *                        options: An array of additional settings that are specific to the field type (default empty)<br>
     *                        value: The default value of the field when it is rendered (default none)<br>
     *                        enabled: Whether the field is enabled when the page is loaded (default true)<br>
     *                        redacted: If true, this field is hidden in debug output (default false)
     *
     * @throws MyRadioException If an attempt is made to set an $options value other than those listed above
     */
    public function __construct($name, $type, $options = [])
    {
        //Set essential parameters
        $this->name = $name;
        $this->type = $type;

        if ($type === self::TYPE_SECTION || $type === self::TYPE_SECTION_CLOSE) {
            $this->required = false;
        }

        //Set optional parameters
        foreach ($options as $k => $v) {
            //Sanity checks - is this a valid parameter and is it not blacklisted?
            if (isset($this->$k) === false && @$this->$k !== null) {
                throw new MyRadioException('Tried to set MyRadioFormField parameter '.$k.' but it does not exist.');
            }
            if (in_array($k, $this->restricted_attributes)) {
                throw new MyRadioException('Tried to set MyRadioFormField parameter '.$k.' but it is not editable.');
            }
            $this->$k = $v;
        }
    }

    /**
     * Returns the name property of this MyRadioFormField.
     *
     * @return string The name of this MyRadioFormField
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get if this needs a value.
     *
     * @return bool
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Get the type of form field.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get if this field is redacted.
     *
     * @return bool
     */
    public function getRedacted()
    {
        return $this->redacted;
    }

    /**
     * Set whether this field needs a value.
     *
     * @param bool $bool
     */
    public function setRequired($bool)
    {
        $this->required = $bool;
    }

    /**
     * Merges the given options to the original ones.
     *
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Sets the value that will be set in this MyRadioFormField.
     *
     * In the case of TABULARSETs, $value may be an array of multiple existing values. You must also provide an extended
     * field name, which is the name of this field, a period '.', and the name of the inner field.
     *
     * @param mixed  $value    The value that this MyRadioFormField will be set to. Type depends on $type parameter.
     * @param string $subfield For TABULARSETs, this is fieldname.innerfieldname.
     */
    public function setValue($value, $subField = null)
    {
        if (strpos($subField, '.') !== false) {
            $subField = explode('.', $subField)[1];
        }
        if ($this->type !== self::TYPE_TABULARSET) {
            $this->value = $value;

            return;
        } else {
            foreach ($this->options as $field) {
                if (!$field instanceof self) {
                    continue;
                }
                if ($field->getName() === $subField) {
                    $field->setValue($value);
                }
            }
        }
    }

    /**
     * Returns a space-separated string of classes that apply to this MyRadioFormField
     * Includes hidden if the MyRadioFormField is set not to display.
     *
     * @return string A space-separated string of classes that apply to this MyRadioFormField
     */
    private function getClasses()
    {
        $classes = '';
        foreach ($this->classes as $class) {
            $classes .= " $class";
        }

        if (!$this->display) {
            $classes .= ' hidden';
        }

        $classes .= ' myradiofrmfield';

        return trim($classes);
    }

    /**
     * Prepares an Array of parameters ready to be sent to the Templater in order to render this MyRadioFormField in a
     *  MyRadioForm.
     *
     * @return array An array of parameters ready to be used in a Template render call
     */
    public function render()
    {
        // If there are MyRadioFormFields in Options, convert these to their render values
        $options = [];
        foreach ($this->options as $k => $v) {
            if ($v instanceof self) {
                $options[$k] = $v->render();
            } else {
                $options[$k] = $v;
            }
        }

        if ($this->type === self::TYPE_ARTIST) {
            $options['artistname'] = $this->value;
        }
        if ($this->type === self::TYPE_FILE) {
            $options['progress_id'] = uniqid();
            $value = null;
        } elseif (($this->type === self::TYPE_TRACK) && !empty($this->value)) {
            if (is_array($this->value)) { //Deal with TABULARSETs
                foreach ($this->value as $k => $v) {
                    if (empty($v)) {
                        continue;
                    }
                    $options['trackname'][$k] = $v->getTitle();
                    $value[$k] = $v->getID();
                }
            } else {
                $options['trackname'] = $this->value->getTitle();
                $value = $this->value->getID();
            }
        } elseif (($this->type === self::TYPE_ALBUM) && !empty($this->value)) {
            if (is_array($this->value)) { //Deal with TABULARSETs
                foreach ($this->value as $k => $v) {
                    if (empty($v)) {
                        continue;
                    }
                    $options['albumname'][$k] = $v->getTitle();
                    $value[$k] = $v->getID();
                }
            } else {
                $options['albumname'] = $this->value->getTitle();
                $value = $this->value->getID();
            }
        } elseif (($this->type === self::TYPE_MEMBER) && !empty($this->value)) {
            if (is_array($this->value)) { //Deal with TABULARSETs
                foreach ($this->value as $k => $v) {
                    if (empty($v)) {
                        continue;
                    }
                    $options['membername'][$k] = $v->getName();
                    $value[$k] = $v->getID();
                }
            } else {
                $options['membername'] = $this->value->getName();
                $value = $this->value->getID();
            }
        } else {
            $value = $this->value;
        }

        return [
            'name' => $this->name,
            'label' => ($this->label === null ? $this->name : $this->label),
            'type' => $this->type,
            'required' => $this->required,
            'explanation' => $this->explanation,
            'class' => $this->getClasses(),
            'options' => $options,
            'value' => $value,
            'enabled' => $this->enabled,
        ];
    }

    /**
     * To be used when getting values from a submitted form, this method returns the correctly type-cast value of the
     * MyRadioFormField depending on the $type parameter.
     *
     * This is called by MyRadioForm::readValues()
     *
     * @param string $prefix The current prefix to the field name
     *
     * @return mixed The submitted field value
     *
     * @throws MyRadioException if the field type does not have a valid read handler
     *
     * @todo   Verify all returns deal with repeated elements correctly
     */
    public function readValue($prefix)
    {
        $name = $prefix.str_replace(' ', '_', $this->name);
        //The easiest ones can just be returned
        switch ($this->type) {
            case self::TYPE_TEXT:
            case self::TYPE_EMAIL:
            case self::TYPE_ARTIST:
                return strip_tags($_REQUEST[$name]);
            break;
            case self::TYPE_BLOCKTEXT:
                $dom = new \DOMDocument();
                $dom->loadHtml($_REQUEST[$name]);

                $xpath = new \DOMXPath($dom);
                while ($node = $xpath->query('//script')->item(0)) {
                    $node->parentNode->removeChild($node);
                }

                return $dom->saveHTML();
            break;
            case self::TYPE_HIDDEN:
            case self::TYPE_PASSWORD:
                return $_REQUEST[$name];
            break;
            case self::TYPE_MEMBER:
                //Deal with Arrays for repeated elements
                if (is_array($_REQUEST[$name])) {
                    for ($i = 0; $i < sizeof($_REQUEST[$name]); ++$i) {
                        if (empty($_REQUEST[$name][$i])) {
                            continue;
                        }
                        $_REQUEST[$name][$i] = MyRadio_User::getInstance($_REQUEST[$name][$i]);
                    }

                    return $_REQUEST[$name];
                } else {
                    if (empty($_REQUEST[$name])) {
                        return;
                    }

                    return MyRadio_User::getInstance($_REQUEST[$name]);
                }
                break;
            case self::TYPE_TRACK:
                //Deal with Arrays for repeated elements
                if (is_array($_REQUEST[$name])) {
                    for ($i = 0; $i < sizeof($_REQUEST[$name]); ++$i) {
                        if (empty($_REQUEST[$name][$i])) {
                            continue;
                        }
                        $_REQUEST[$name][$i] = MyRadio_Track::getInstance($_REQUEST[$name][$i]);
                    }

                    return $_REQUEST[$name];
                } else {
                    return MyRadio_Track::getInstance($_REQUEST[$name]);
                }
                break;
            case self::TYPE_NUMBER:
            case self::TYPE_SELECT:
            case self::TYPE_RADIO:
            case self::TYPE_DAY:
                //Deal with Arrays for repeated elements
                if (is_array($_REQUEST[$name])) {
                    for ($i = 0; $i < sizeof($_REQUEST[$name]); ++$i) {
                        if (is_numeric($_REQUEST[$name][$i])) {
                            $_REQUEST[$name][$i] = (int) $_REQUEST[$name][$i];
                        }
                    }

                    return $_REQUEST[$name];
                } else {
                    if (is_numeric($_REQUEST[$name])) {
                        return (int) $_REQUEST[$name];
                    } else {
                        return $_REQUEST[$name];
                    }
                }
                break;
            case self::TYPE_DATE:
            case self::TYPE_DATETIME:
            case self::TYPE_TIME:
                //Deal with repeated elements
                if (is_array($_REQUEST[$name])) {
                    for ($i = 0; $i < sizeof($_REQUEST[$name]); ++$i) {
                        $_REQUEST[$name][$i] = $this->convertTime($_REQUEST[$name][$i]);
                    }

                    return $_REQUEST[$name];
                } else {
                    return $this->convertTime($_REQUEST[$name]);
                }
                break;
            case self::TYPE_CHECK:
                return (bool) (isset($_REQUEST[$name]) && ($_REQUEST[$name] === 'On' || $_REQUEST[$name] === 'on'));
                break;
            case self::TYPE_CHECKGRP:
                $return = [];
                foreach ($this->options as $option) {
                    $return[$option->getName()] = (int) $option->readValue($name.'-');
                }

                return $return;
                break;
            case self::TYPE_FILE:
                return $_FILES[$name];
                break;
            case self::TYPE_TABULARSET:
                $return = [];
                $clearNulls = [];
                foreach ($this->options as $option) {
                    if ($option->getType() === self::TYPE_DAY) {
                        $clearNulls[] = $option->getName();
                    }
                    $return[$option->getName()] = $option->readValue($prefix);
                }

                $fields = array_keys($return);

                //Explicitly set Days to null if the rest of the row is
                //0 is treated as empty, so let's clear that up and advise using is_null
                foreach ($clearNulls as $field) {
                    foreach ($return[$field] as $i => $v) {
                        if ($v > 0) {
                            continue;
                        }
                        $hasValue = false;
                        foreach ($fields as $other) {
                            if ($other !== $field && !is_null($return[$other][$i])) {
                                $hasValue = true;
                                break;
                            }
                        }
                        if (!$hasValue) {
                            $return[$field][$i] = null;
                        }
                    }
                }

                //Clear rows that are entirely null
                $removeKeys = [];
                for ($i = 0; $i < sizeof($return[$fields[0]]); ++$i) {
                    $hasValue = false;
                    foreach ($fields as $field) {
                        if (!is_null($return[$field][$i])) {
                            $hasValue = true;
                            break;
                        }
                    }
                    if (!$hasValue) {
                        $removeKeys[] = $i;
                    }
                }
                if (!empty($removeKeys)) {
                    foreach ($fields as $field) {
                        foreach ($removeKeys as $key) {
                            unset($return[$field][$key]);
                        }
                        //Reset indexes
                        $return[$field] = array_values($return[$field]);
                    }
                }

                return $return;
                break;
            case self::TYPE_SECTION:
            case self::TYPE_SECTION_CLOSE:
                return;
                break;
            case self::TYPE_ALBUM:
                //Deal with Arrays for repeated elements
                if (is_array($_REQUEST[$name])) {
                    for ($i = 0; $i < sizeof($_REQUEST[$name]); ++$i) {
                        if (empty($_REQUEST[$name][$i])) {
                            continue;
                        }
                        $_REQUEST[$name][$i] = MyRadio_Album::getInstance($_REQUEST[$name][$i]);
                    }

                    return $_REQUEST[$name];
                } else {
                    return MyRadio_Album::getInstance($_REQUEST[$name]);
                }
                break;
            case self::TYPE_WEEKSELECT:
                /*
                 * Now isn't this fun. The week select is comprised of 336 checkboxes that we need to amalgamate.
                 * Value returned are relative to days starting at midnight, not 7am.
                 * Selections spanning multiple days will return as two seperate selections ('days' here again being midnight)
                 */
                $times = [];
                $active_day = null;
                $active_time = null;
                for ($i = 1; $i <= 7; ++$i) { //Iterate over each day
                    for ($j = 0; $j < 86400; $j += 1800) { //Iterate over each 30 minute interval
                        if (isset($_REQUEST[$name.'-'.$i.'-'.$j])
                        && strtolower($_REQUEST[$name.'-'.$i.'-'.$j]) === 'on'
                        ) {
                            //Is there already an active selection? If so, carry on.
                            if ($active_day !== null) {
                                //Yep, nothing to see here.
                                continue;
                            } else {
                                //Ooh, this is the start of a new range
                                $active_day = $i;
                                $active_time = $j;
                            }
                        } else {
                            //Was there an active selection? If so, kill it.
                            if ($active_day !== null) {
                                //Add the current state to times
                                $times[] = ['day' => $active_day, 'start_time' => $active_time, 'end_time' => $j - 1];
                                $active_day = null;
                                $active_time = null;
                            }
                            //If there wasn't an active selection, we don't have to do anything.
                        }
                    }
                    //At the end of the day, kill any active selection.
                    if ($active_day !== null) {
                        $times[] = ['day' => $active_day, 'start_time' => $active_time, 'end_time' => 86399];
                        $active_day = null;
                        $active_time = null;
                    }
                }

                return $times;
                break;
            default:
                throw new MyRadioException('Field type '.$this->type.' does not have a valid value interpreter definition.');
        }
    }

    private function convertTime($timeString)
    {
        if ($timeString === '') {
            return;
        }
        /* For why we need to do this, consult the notes at
         * http://php.net/manual/en/function.strtotime.php.
         * YES, PHP IS RETARDED.
         */
        $timeString = str_replace('/', '-', $timeString);
        $time = (int) strtotime($timeString);
        //Times should be seconds since midnight *any* day
        if ($this->type === self::TYPE_TIME) {
            $time -= strtotime('Midnight');

            // Handle timezones savings pushing this over the day boundary
            if ($time >= 86400) {
                $time -= 86400;
            } elseif ($time < 0) {
                $time += 86400;
            }
        }

        return $time;
    }
}
