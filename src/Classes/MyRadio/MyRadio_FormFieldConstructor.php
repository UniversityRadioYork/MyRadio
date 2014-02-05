<?php

/**
 * MyRadio_FormFieldConstructor class.
 *
 * @version 20140113
 * @author  Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Core
 */

/**
 * A method object that constructs a field given a name and array
 * representation of its specification.
 *
 * @version 20140113
 * @author  Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Core
 */
class MyRadio_FormFieldConstructor
{
    /**
     * The name of the field being constructed.
     * @var string
     */
    protected $name;

    /**
     * The field specification.
     * @var array
     */
    protected $field;

    /**
     * The binding array.
     * @var array
     */
    protected $bindings;

    /**
     * The form constructor.
     * @var MyRadio_FormConstructor
     */
    protected $fc;

    /**
     * A ReflectionClass used to introspect the form field class.
     * @var ReflectionClass
     */
    protected $rc;

    /**
     * Constructs a new MyRadio_FormFieldConstructor.
     *
     * @param string                  $name  The field name.
     * @param array                   $field The field specification.
     * @param MyRadio_FormConstructor $fc    The form constructor.
     */
    public function __construct(
        $name,
        array $field,
        MyRadio_FormConstructor $fc,
        array $bindings
    ) {
        $this->name = $name;
        $this->field = $field;
        $this->fc = $fc;
        $this->bindings = $bindings;
        $this->rc = new ReflectionClass('MyRadioFormField');
    }
}
