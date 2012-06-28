<?php

/**
 * Abstractor for MyURY Form Definitions
 * 
 * A MyURYForm object is used as follows
 *  
 * - A Form definition PHP file that defines the form elements and parameters
 * - A Form setter that sets the values of the form
 * - A Form getter that gets the vales of the form
 * - A Form Viewer that loads the Form definition and sets the values
 * - A Form Saver that loads the Form definiton, reads submitted values
 *   and calls getter to interpret them
 */
class MyURYForm {

  /**
   * The name of the form
   * @var string 
   */
  private $name = 'autofrm';

  /**
   * The module that it will submit to
   * @var string 
   */
  private $module;

  /**
   * The action that it will submit to
   * @var string 
   */
  private $action;

  /**
   * Whether to enable detailed output of what is happening (or isn't)
   * @var boolean 
   */
  private $debug = false;

  /**
   * Additional classes to add to the base form element
   * @var array 
   */
  private $classes = array();

  /**
   * Whether to enable Form Validation
   * @var boolean 
   */
  private $validate = true;

  /**
   * Whether to use GET instead of POST 
   */
  private $get = false;

  /**
   * The Twig template to use for the form. Must be form.twig or a child.
   * @var string 
   */
  private $template = 'form.twig';

  /**
   * The form fields in the form (an array of MyURYFormField objects)
   * @var array 
   */
  private $fields = array();
  
  /**
   * The title of the page (the human readable name)
   * @var string
   */
  private $title = null;
  
  /**
   * The module of the page
   * @var string
   */
  private $module = null;

  /**
   * Logging output
   * @var array 
   */
  private $debug_log = array();

  /**
   * Fields that cannot be edited by params
   * @var array 
   */
  private $restricted_fields = array('name', 'module', 'action', 'fields', 'restricted_fields', 'debug_log');

  /**
   * Creates a new MyURYForm object with the given parameters
   * @param string $name The name/id of the form
   * @param string $module The module the form submits to
   * @param string $action The action the form submits to
   * @param array $params One or more of the following additional settings
   * debug - Verbose logging output - default false
   * classes - An array of additional classes to apply to the form - default empty
   * validate - Whether to validate the field input client-side - default true
   * get - Whether to use the GET submission method - default false
   * template - The Twig template to use for the form - default form.twig
   * 
   * @throws MyURYException Thrown on failure of a sanity check
   */
  public function __construct($name, $module, $action, $params = array()) {
    //Sanity check - does the target exist?
    if (!CoreUtils::isValidController($module, $action)) {
      throw new MyURYException('The Module/Action target of this MyURYForm is invalid.');
    }
    //Set essential parameters
    $this->name = $name;
    $this->module = $module;
    $this->action = $action;

    //Check all optional parameters
    foreach ($params as $k => $v) {
      //Sanity checks - is this a valid parameter and is it not blacklisted?
      if (isset($this->$k) === false && @$this->$k !== null)
        throw new MyURYException('Tried to set MyURYForm parameter ' . $k . ' but it does not exist.');
      if (in_array($k, $this->restricted_fields))
        throw new MyURYException('Tried to set MyURYForm parameter ' . $k . ' but it is not editable.');
      $this->$k = $v;
    }
  }

  public function addField(MyURYFormField $field) {
    //Sanity check - is this name in use
    foreach ($this->fields as $f) {
      if ($f->getName() === $field->getName())
        throw new MyURYException('Tried to create a duplicate MyURYFormField ' . $f->getName());
    }
    $this->fields[] = $field;
    return $this;
  }

  public function render() {
    $fields = array();
    foreach ($this->fields as $field) {
      $fields[] = $field->render();
    }
    CoreUtils::getTemplateObject()->setTemplate($this->template)
            ->addVariable('classes', $this->getClasses())
            ->addVariable('action', CoreUtils::makeURL($this->module, $this->action))
            ->addVariable('method', $this->get ? 'get' : 'post')
            ->addVariable('name', User::getInstance()->getName())
            ->addVariable('title', isset($this->title) ? $this->title : $this->name)
            ->addVariable('module', isset($this->module) ? $this->module : $this->name)
            ->addVariable('fields', $fields)
            ->render();
  }

  private function getClasses() {
    $classes = 'myuryfrm';
    foreach ($this->classes as $class) {
      $classes .= " $class";
    }

    return $classes;
  }

}