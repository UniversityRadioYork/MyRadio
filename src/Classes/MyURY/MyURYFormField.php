<?php

/**
 * An input of some description that will be rendered in a form
 * A collection of these is automatically created when building a MyURYForm
 * 
 * @package MyURY_Core
 * @version 21072012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 */
class MyURYFormField {

  const TYPE_TEXT      = 0x00;
  const TYPE_NUMBER    = 0x01;
  const TYPE_EMAIL     = 0x02;
  const TYPE_DATE      = 0x03;
  const TYPE_DATETIME  = 0x04;
  const TYPE_MEMBER    = 0x05;
  const TYPE_TRACK     = 0x06;
  const TYPE_ARTIST    = 0x07;
  const TYPE_HIDDEN    = 0x08;
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
   * Set up a new MyURY Form Field with the new parameters, returning the new field
   * This method is only useful practically when the MyURYFormField is inserted to a MyURYForm
   * @param String $name The name and id of the field, as used in the HTML properties - should be unique to the form
   * @param int $type The MyURYFormField Field Type to use. See the constants defined in this class for details
   * @param Array $options A set of additional settings for the MyURYFormField as follows (all optional):
   *   required: Whether the field is required (default true)
   *   label: The human-readable name of the field. (default reuses name)
   *   explanation: Help text for the MyURYFormField (default none)
   *   display: Whether the MyURYFormField should be visible when the page loads (default true)
   *   classes: An array of additional classes to add to the input field (default empty)
   *   options: An array of additional settings that are specific to the field type (default empty)
   *   value: The default value of the field when it is rendered (default none)
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
   * MyURYFormField depending on the $type parameter
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