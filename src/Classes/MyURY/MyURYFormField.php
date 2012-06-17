<?php
/**
 * An input of some description that will be rendered in a form
 * A collection of these is automatically created when building a MyURYForm
 *
 * @author lpw
 */
class MyURYFormField {
  const TYPE_TEXT     = 0x00;
  const TYPE_NUMBER   = 0x01;
  const TYPE_EMAIL    = 0x02;
  const TYPE_DATE     = 0x03;
  const TYPE_DATETIME = 0x04;
  const TYPE_MEMBER   = 0x05;
  const TYPE_TRACK    = 0x06;
  const TYPE_ARTIST   = 0x07;
  const TYPE_HIDDEN   = 0x08;
  const TYPE_SELECT   = 0x09;
  const TYPE_RADIO    = 0x0A;
  const TYPE_CHECK    = 0x0B;
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
   * For selects, radios and checkboxes 
   */
}