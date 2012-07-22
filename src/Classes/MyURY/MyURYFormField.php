<?php
/**
 * This file provides the MyURYFormField class for MyURY
 * @package MyURY_Core
 */

/**
 * An input of some description that will be rendered in a form
 * A collection of these is automatically created when building a MyURYForm
 * 
 * @package MyURY_Core
 * @version 21072012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 */
class MyURYFormField {

  /**
   * The constant used to specify this MyURYFormField should be a standard text field.
   * 
   * A text field can take the following custom options:
   * 
   * minlength: The minimum number of characters the user must enter for this to be valid input
   * 
   * maxlength: The maximum number of characters the user can enter for this to be valid input
   */
  const TYPE_TEXT      = 0x00;
  /**
   * The constant used to specify this MyURYFormField should be a standard number field.
   * 
   * A number field can take the following custom options:
   * 
   * min: The lowest number the user must enter for this to be valid input
   * 
   * max: The highest number the user can enter for this to be valid input
   */
  const TYPE_NUMBER    = 0x01;
  /**
   * The constant used to specify this MyURYFormField must be a text field that validates as an email address
   * 
   * The email field takes no custom options.
   */
  const TYPE_EMAIL     = 0x02;
  /**
   * The constant used to specify this MyURYFormField must be a valid date, and provides a datepicker widget for it
   * 
   * The date field currently takes no custom options.
   * 
   * @todo Support for mindate and maxdate
   */
  const TYPE_DATE      = 0x03;
  /**
   * The constant used to specify this MyURYFormField must be a valid date and time, providing a datetime widget for it
   * 
   * The datetime field currently takes no custom options.
   * NOTE: Currently, the TIME aspect must be in 15 minute intervals
   * 
   * @todo Support for a custom time interval
   * 
   * @todo Support for mindate and maxdate
   * 
   * @todo Support for mintime and maxtime
   */
  const TYPE_DATETIME  = 0x04;
  /**
   * The constant used to specify this MyURYFormField must be a valid member, providing a Member autocomplete for it.
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
  const TYPE_MEMBER    = 0x05;
  /**
   * The constant used to specify this MyURYFormField must be a valid track, providing a Track autocomplete for it.
   * This actually renders two fields - the visible one the user can enter a track into, and a hidden one that will
   * store the ID once it has been selected.
   * 
   * The track field takes the following custom options:
   * 
   * trackname: Since value will set the hidden integer value, this can be used to set the text value of the visible
   * element when loading a pre-filled form.
   * 
   * @todo Support for filtering to only digitised, clean tracks etc.
   */
  const TYPE_TRACK     = 0x06;
  /**
   * The constant used to specify this MyURYFormField must be a valid artist, providing an Artist autocomplete for it.
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
  const TYPE_ARTIST    = 0x07;
  /**
   * The constant used to specify this MyURYFormField must be a standard HTML hidden field type.
   * 
   * The hidden field takes no custom options.
   */
  const TYPE_HIDDEN    = 0x08;
  /**
   * The constant used to specify this MyURYFormField must be a standard HTML select field.
   * 
   * The Custom Options properly for this MyURYFormField type is an Array of items in the select list, each defined as
   * follows:
   * 
   * value: The value of the select option. This MUST be an integer.
   * 
   * disabled: If true, this option can not be selected (default false)
   * 
   * text: The human-readable value of the option that is displayed in the select dropdown.
   */
  const TYPE_SELECT    = 0x09;
  const TYPE_RADIO     = 0x0A;
  const TYPE_CHECK     = 0x0B;
  const TYPE_DAY       = 0x0C;
  const TYPE_BLOCKTEXT = 0x0D;
  const TYPE_TIME      = 0x0E;
  const TYPE_CHECKGRP  = 0x0F;

  /**
   * The name/id of the Form Field
   * @var string 
   */
  private $name;

  /**
   * The type of the form field
   * @var int
   */
  private $type;

  /**
   * Whether input in this field is required
   * @var bool
   */
  private $required = true;

  /**
   * The label of the field (null = use name)
   * @var string
   */
  private $label = null;

  /**
   * Helpful text explaining the form field
   * @var string
   */
  private $explanation = '';

  /**
   * Whether the form element should be visible
   * @var bool
   */
  private $display = true;

  /**
   * Additional classes to add to the field
   * @var array
   */
  private $classes = array();

  /**
   * For selects, radios and checkboxes only - the options to display
   * @var 2D Array as defined
   * {display: 'Value to Display', enabled: true}
   */
  private $options = array();

  /**
   * The value of the form field
   * @var mixed 
   */
  private $value = null;
  
  /**
   * Whether the field is enabled/disabled by default
   * Actually renders as readonly in most cases
   * @var bool
   */
  private $enabled = true;

  /**
   * Settings that cannot be altered by the $options parameter
   * @var array 
   */
  private $restricted_attributes = array('restricted_attributes', 'value', 'name', 'type');

  /**
   * Set up a new MyURY Form Field with the new parameters, returning the new field.
   * This method is only useful practically when the MyURYFormField is inserted to a MyURYForm
   * @param String $name The name and id of the field, as used in the HTML properties - should be unique to the form
   * @param int $type The MyURYFormField Field Type to use. See the constants defined in this class for details
   * @param Array $options A set of additional settings for the MyURYFormField as follows (all optional):<br>
   *   required: Whether the field is required (default true)<br>
   *   label: The human-readable name of the field. (default reuses name)<br>
   *   explanation: Help text for the MyURYFormField (default none)<br>
   *   display: Whether the MyURYFormField should be visible when the page loads (default true)<br>
   *   classes: An array of additional classes to add to the input field (default empty)<br>
   *   options: An array of additional settings that are specific to the field type (default empty)<br>
   *   value: The default value of the field when it is rendered (default none)<br>
   *   enabled: Whether the field is enabled when the page is loaded (default true)  
   * @throws MyURYException If an attempt is made to set an $options value other than those listed above
   */
  public function __construct($name, $type, $options = array()) {
    //Set essential parameters
    $this->name = $name;
    $this->type = $type;

    //Set optional parameters
    foreach ($options as $k => $v) {
      //Sanity checks - is this a valid parameter and is it not blacklisted?
      if (isset($this->$k) === false && @$this->$k !== null)
        throw new MyURYException('Tried to set MyURYFormField parameter ' . $k . ' but it does not exist.');
      if (in_array($k, $this->restricted_attributes))
        throw new MyURYException('Tried to set MyURYFormField parameter ' . $k . ' but it is not editable.');
      $this->$k = $v;
    }
  }
  
  /**
   * Returns the name property of this MyURYFormField
   * @return String The name of this MyURYFormField
   */
  public function getName() {
    return $this->name;
  }
  
  /**
   * Sets the value that will be set in this MyURYFormField
   * @param mixed $value The value that this MyURYFormField will be set to. Type depends on $type parameter.
   */
  public function setValue($value) {
    $this->value = $value;
  }

  /**
   * Returns a space-separated string of classes that apply to this MyURYFormField
   * Includes ui-helper-hidden if the MyURYFormField is set not to display
   * @return string A space-separated string of classes that apply to this MyURYFormField
   */
  private function getClasses() {
    $classes = 'myuryfrmfield';
    foreach ($this->classes as $class) {
      $classes .= " $class";
    }
    
    if (!$this->display) $classes .= ' ui-helper-hidden';

    return $classes;
  }

  /**
   * Prepares an Array of parameters ready to be sent to the Templater in order to render this MyURYFormField in a
   *  MyURYForm
   * @return Array An array of parameters ready to be used in a Template render call
   */
  public function render() {
    // If there are MyURYFormFields in Options, convert these to their render values
    $options = array();
    foreach ($this->options as $k => $v) {
      if ($v instanceof self) {
        $options[$k] = $v->render();
      } else {
        $options[$k] = $v;
      }
    }
    return array(
        'name'        => $this->name,
        'label'       => ($this->label === null ? $this->name : $this->label),
        'type'        => $this->type,
        'required'    => $this->required,
        'explanation' => $this->explanation,
        'class'       => $this->getClasses(),
        'options'     => $options,
        'value'       => $this->value,
        'enabled'     => $this->enabled
    );
  }
  
  /**
   * To be used when getting values from a submitted form, this method returns the correctly type-cast value of the
   * MyURYFormField depending on the $type parameter.
   * 
   * This is called by MyURYForm::readValues()
   * @param String $prefix The current prefix to the field name
   * @return mixed The submitted field value
   * @throws MyURYException if the field type does not have a valid read handler
   */
  public function readValue($prefix) {
    $name = $prefix . str_replace(' ', '_', $this->name);
    //The easiest ones can just be returned
    switch ($this->type) {
      case self::TYPE_TEXT:
      case self::TYPE_EMAIL:
      case self::TYPE_ARTIST:
      case self::TYPE_HIDDEN:
      case self::TYPE_BLOCKTEXT:
      case self::TYPE_TIME:
        return (string)$_REQUEST[$name];
        break;
      case self::TYPE_NUMBER:
      case self::TYPE_MEMBER:
      case self::TYPE_TRACK:
      case self::TYPE_SELECT:
      case self::TYPE_RADIO:
      case self::TYPE_DAY:
        return (int)$_REQUEST[$name];
        break;
      case self::TYPE_DATE:
      case self::TYPE_DATETIME:
        return (int)strtotime($_REQUEST[$name]);
        break;
      case self::TYPE_CHECK:
        return (bool)(isset($_REQUEST[$name]) && ($_REQUEST[$name] === 'On' || $_REQUEST[$name] === 'on'));
        break;
      case self::TYPE_CHECKGRP:
        $return = array();
        foreach ($this->options as $option) {
          $return[$option->getName()] = (int)$option->readValue($name.'-');
        }
        return $return;
        break;
      default:
        throw new MyURYException('Field type ' . $this->type . ' does not have a valid renderer definition.');
    }
  }

}