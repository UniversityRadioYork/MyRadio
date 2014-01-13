<?php

/**
 * MyRadio_NormalFormFieldConstructor class.
 *
 * @version 20140113
 * @author  Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Core
 */

/**
 * A method object that constructs a MyRadioFormField given a name and array
 * representation of its specification.
 *
 * @version 20140113
 * @author  Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Core
 */
class MyRadio_NormalFormFieldConstructor extends MyRadio_FormFieldConstructor {
    /**
     * Builds and attaches the field.
     *
     * @return null  Nothing.
     */
    public function make() {
        $type = $this->getTypeConstant($this->field['type']);

        // The constructor will complain if these are passed into the parameters
        // array.
        unset($this->field['name']);
        unset($this->field['type']);

        $this->doBinding();

        $this->fc->constructAndAddField($this->name, $type, $this->field);
    }

    /**
     * Performs binding of !bind(foo) strings in a field description to their
     * entries in the binding array.
     *
     * @param array $this->field  The field description array to bind on.
     */
    private function doBinding() {
        foreach($this->field as $key => &$value) {
            if ($this->fc->isSpecialFieldName($value)) {
                $matches = [];
                if (preg_match('/^!bind\( *(\w+) *\)$/', $value, $matches)) {
                    if (array_key_exists($matches[1], $this->bindings)) {
                        $value = $this->bindings[$matches[1]];
                    } else {
                        throw new MyRadioException(
                            'Tried to !bind to unbound form variable: ' . $matches[1] . '.'
                        );
                    }
                }
            }
        }
    }

    /**
     * Infers a type constant (TYPE_XYZ) from a case insensitive name.
     *
     * @param string $name  The name of the type constant.
     *
     * @return int  The type constant.
     */
    private function getTypeConstant($name) {
        return $this->rc->getconstant('TYPE_' . strtoupper($name));
    }
}

?>
