<?php
/**
 * An input of some description that will be rendered in a form
 * A collection of these is automatically created when building a MyURYForm
 *
 * @author lpw
 */
class MyURYFormField {
  const TYPE_TEXT     = 0;
  const TYPE_NUMBER   = 1;
  const TYPE_EMAIL    = 2;
  const TYPE_DATE     = 3;
  const TYPE_DATETIME = 4;
  const TYPE_MEMBER   = 5;
  const TYPE_TRACK    = 6;
  const TYPE_ARTIST   = 7;
  const TYPE_HIDDEN   = 8;
  const TYPE_SELECT   = 9;
  const TYPE_RADIO    = 10;
  /**
   * The name/id of the Form Field
   * @var string 
   */
  private $name;
  /**
   * The type of the form field
   * @var type 
   */
  private $type;
  private $required = true;
  private $label = null;
  private $explanation = '';
  private $display = true;
  private $classes = array();
}