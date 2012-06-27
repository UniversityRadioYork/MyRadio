<?php

/**
 * An input of some description that will be rendered in a form
 * A collection of these is automatically created when building a MyURYForm
 *
 * @author lpw
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

  public function getName() {
    return $this->name;
  }

  private function getClasses() {
    $classes = 'myuryfrmfield';
    foreach ($this->classes as $class) {
      $classes .= " $class";
    }
    
    if (!$this->display) $classes .= ' ui-helper-hidden';

    return $classes;
  }

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
        'options'     => $this->options,
        'value'       => $this->value,
        'enabled'     => $this->enabled
    );
  }

}