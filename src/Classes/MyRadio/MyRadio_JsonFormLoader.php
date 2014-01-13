<?php
/**
  * MyRadio_JsonFormLoader class.
  * @version 20131002
  * @author Matt Windsor <matt.windsor@ury.org.uk>
  * @package MyRadio_Core
  */

/**
 * A loader for forms written declaratively in JSON format.
 *
 * The format is thus:
 * {
 *   'name': 'form_name',
 *   'module': 'module_name',
 *   'action': 'form_completion_action',
 *   'options': { ... },
 *   'fields': {
 *     'field_name': {
 *       'type': 'constant name without TYPE_, case insensitive',
 *       'label': 'etc etc',
 *       'options': { ... }
 *     }
 *   }
 * }
 *
 * @version 20130428
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Core
 */
class MyRadio_JsonFormLoader {
    /**
     * The name of the current MyRadio module.
     * @var string
     */
    private $module;

    /**
     * The internal form representation, ready to render to a form.
     * @var array
     */
    private $form_array;

    /**
     * Constructs a new MyRadio_JsonFormLoader.
     *
     * @param string $module  The name of the calling MyRadio module.
     *
     * @return MyRadio_JsonFormLoader
     */
    public function __construct($module) {
        $this->module = $module;
        $this->form_array = null;
    }
  
    /**
     * Loads a form from its filename, from the module's forms directory.
     *
     * @param string $name  The (file)name of the form, without the '.json'.
     * @return MyRadio_JsonFormLoader  this.
     */
    public function fromName($name) {
        return $this->fromPath(
            'Models/' . $this->module . '/' . $name . '.json'
        );
    }

    /**
     * Loads a form from its file path.
     *
     * @param string $path  The path to load from.
     * @return MyRadio_JsonFormLoader  this.
     */
    public function fromPath($path) {
        return $this->fromString(
            file_get_contents($path, true)
        );
    }
    
    /**
     * Loads a form from a JSON string.
     *
     * @param string $str  The string to load from.
     * @return MyRadio_JsonFormLoader  this.
     */
    public function fromString($str) {
        $this->form_array = json_decode($str, true);
        if ($this->form_array === null) {
            throw new MyRadioException(
                'Failed to load form from JSON: Code ' .
                json_last_error() 
            );
        }
        return $this;
    }

    /**
     * Compiles a previously loaded form to a form object.
     *
     * @param string    $action  The name of the action to trigger on submission.
     * @param array     $binds   The mapping of names used in !bind directives to
     *                           variables.
     *
     * @return MyRadioForm  The processed form.
     */
    public function toForm($action, array $binds=[]) {
        $fc = new MyRadio_FormConstructor(
            $this->form_array,
            $binds,
            $this->module,
            $action
        );
        return $fc->toForm();
    }

    /**
     * Loads and renders a form from its MyRadio module and name.
     *
     * This is a convenience wrapper for 'fromPath'.
     *
     * @param string $module  The name of the calling MyRadio module.
     * @param string $name    The (file)name of the form, without the '.json'.
     * @param string $action  The name of the action to trigger on submission.
     * @return MyRadioForm  The processed form.
     */
    public static function loadFromModule($module, $name, $action, $binds=[]) {
        return (
            new MyRadio_JsonFormLoader($module)
        )->fromName($name)->toForm($action, $binds);
    }
}

?>
