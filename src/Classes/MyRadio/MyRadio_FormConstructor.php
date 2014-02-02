<?php

/**
 * MyRadio_FormConstructor class.
 *
 * @version 20140113
 * @author  Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Core
 */

/**
 * A method object that constructs a MyRadioForm given an array representation
 * of its specification.
 *
 * This is used by the FormLoaders to create a form after it has been parsed.
 *
 * @version 20140113
 * @author  Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Core
 */
class MyRadio_FormConstructor
{
    /**
     * The prefix for strings that signify special processing directives.
     * @const string
     */
    const SPECIAL_PREFIX = '!';

    /**
     * The name of the MyRadio module to which the form will submit.
     * @var string
     */
    private $module;

    /**
     * The name of the MyRadio action to which the form will submit.
     * @var string
     */
    private $action;

    /**
     * A list of current bindings of variables in the form.
     * @var array
     */
    private $bindings;

    /**
     * The internal form representation, ready to render to a form.
     * @var array
     */
    private $form_array;

    /**
     * The form being constructed.
     * @var MyRadioForm
     */
    private $form;

    /**
     * Constructs a new MyRadio_FormConstructor.
     *
     * @param array  $form_array The array representation of the form.
     * @param array  $bindings   A map of bindings of variables to substitute
     *                           into the form wherever a !bind directive is
     *                           found.
     * @param string $module     The module to which the form will submit.
     * @param string $action     The action to which the form will submit.
     */
    public function __construct(
        array  $form_array,
        array  $bindings,
        $module,
        $action
    ) {
        $this->form_array = $form_array;
        $this->bindings   = $bindings;
        $this->module     = $module;
        $this->action     = $action;
        $this->form       = null;
    }

    /**
     * Constructs a MyRadioForm from its array representation.
     *
     * @return MyRadioForm The finished form.
     */
    public function toForm()
    {
        $this->makeBareForm();
        $this->addInitialFields();

        return $this->form;
    }

    /**
     * Constructs a bare form with no fields.
     *
     * @return null Nothing.
     */
    private function makeBareForm()
    {
        $this->form = new MyRadioForm(
            $this->form_array['name'],
            $this->module,
            $this->action,
            $this->form_array['options']
        );
    }

    /**
     * Adds the fields in the form specification to the form.
     *
     * @return null Nothing.
     */
    private function addInitialFields()
    {
        foreach ($this->form_array['fields'] as $name => $field) {
            $this->addFieldToForm($name, $field, $this->bindings);
        }
    }

    /**
     * Compiles a field description into a field and adds it to the given form.
     *
     * @param string $name     The name of the field.
     * @param array  $field    The field description array to compile.
     * @param array  $bindings The set of variable bindings to give to
     *                         the field constructor.
     *
     * @return Nothing.
     */
    public function addFieldToForm(
        $name,
        array  $field,
        array  $bindings
    ) {
        return $this->getFieldConstructorClass($name)
                    ->newInstanceArgs([$name, $field, $this, $bindings])
                    ->make();
    }

    /**
     * Deduces the appropriate form field constructor to use for a field.
     *
     * @param string $name The name of the field.
     *
     * @return ReflectionClass A reflection class for the field constructor.
     */
    private function getFieldConstructorClass($name)
    {
        if ($this->isSpecialFieldName($name)) {
            $class = 'MyRadio_SpecialFormFieldConstructor';
        } else {
            $class = 'MyRadio_NormalFormFieldConstructor';
        }

        return new ReflectionClass($class);
    }

    /**
     * Constructs and adds a fully built field to the form.
     *
     * @param string $name    The name of the field.
     * @param int    $type    The type enumerator of the field.
     * @param array  $options The options to pass to the field constructor.
     *
     * @return null Nothing.
     */
    public function constructAndAddField($name, $type, array $options)
    {
        $this->form->addField(
            new MyRadioFormField($name, $type, $options)
        );
    }

    /**
     * Determines whether a field name denotes a special field.
     *
     * @param string $name The field name.
     *
     * @return boolean True if the field is special; false otherwise.
     */
    public function isSpecialFieldName($name)
    {
        return is_string($name) && (strpos($name, self::SPECIAL_PREFIX) === 0);
    }
}
