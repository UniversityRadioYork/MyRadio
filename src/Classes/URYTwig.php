<?php
require_once 'Interfaces/TemplateEngine.php';
/**
 * Singleton class for the Twig template engine
 *
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 * @version 22052012
 * @depends Config
 */
class URYTwig extends Twig_Environment implements TemplateEngine {
  private static $me;
  private $contextVariables = array();
  private $template;
  
  /**
   * Cannot be private - parent does not allow it
   */
  public function __construct() {
    $twig_loader = new Twig_Loader_Filesystem(__DIR__.'/../Templates/');
    $this->contextVariables['notices'] = '';
    parent::__construct($twig_loader, array('auto_reload' => true));
    if (Config::$template_debug) {
      $this->enableDebug();
    }
    print_r($this);
    $this->addVariable('name', isset($_SESSION['name']) ? $_SESSION['name'] : 'Anonymous');
    $this->addVariable('timeslotname', isset($_SESSION['timeslotname']) ? $_SESSION['timeslotname'] : null);
    
  }
  
  /**
   * Registers a new variable to be passed to the template
   * @param String $name The name of the variable
   * @param mixed $value The value of the variable - literally any valid type
   * @return URYTwig This for chaining
   */
  public function addVariable($name, $value) {
    if ($name === 'notices') {
      throw new MyURYException('Notices cannot be directly set via the Template Engine');
    }
    $this->contextVariables[$name] = $value;
    return $this;
  }
  
  public function addInfo($message, $icon = 'info') {
    $this->contextVariables['notices'][] = array('icon' => $icon, 'message' => $message, 'state' => 'highlight');
  }
  
  public function addError($message, $icon = 'alert') {
    $this->contextVariables['notices'][] = array('icon' => $icon, 'message' => $message, 'state' => 'error');
  }
  
  /**
   * Sets the template file to use
   * @param String $template The template filename
   * @throws MyURYException If template does not exist
   * @return URYTwig This for chaining
   */
  public function setTemplate($template) {
    if (!file_exists(__DIR__.'/../Templates/'.$template)) {
      throw new MyURYException("Template $template does not exist");
    }
    $this->template = $this->loadTemplate($template);
    return $this;
  }
  
  /**
   * Renders the template
   */
  public function render() {
    echo $this->template->render($this->contextVariables);
  }
  
  public static function getInstance() {
    if (!self::$me) {
      self::$me = new self();
    }
    return self::$me;
  }
}
