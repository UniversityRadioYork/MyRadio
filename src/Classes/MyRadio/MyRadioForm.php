<?php

namespace MyRadio\MyRadio;

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadioException;
use MyRadio\Config;

/**
 * This file provides the MyRadioForm class for MyRadio.
 */

/**
 * Abstractor for MyRadio Form Definitions.
 *
 * A MyRadioForm object is used as follows
 *
 * - A Form definition PHP file that defines the form elements and parameters
 * - A Form setter that sets the values of the form
 * - A Form getter that gets the vales of the form
 * - A Form Viewer that loads the Form definition and sets the values
 * - A Form Saver that loads the Form definiton, reads submitted values
 *   and calls getter to interpret them
 */
class MyRadioForm
{
    /**
     * The name of the form.
     *
     * @var string
     */
    private $name = 'autofrm';

    /**
     * The module that it will submit to
     * Best practice is this should be the current module.
     *
     * @var string
     */
    private $module;

    /**
     * The action that it will submit to.
     *
     * @var string
     */
    private $action;

    /**
     * Whether to enable detailed output of what is happening (or isn't).
     *
     * @var bool
     */
    private $debug = false;

    /**
     * Additional classes to add to the base form element.
     *
     * @var array
     */
    private $classes = [];

    /**
     * Whether to enable Form Validation.
     *
     * @var bool
     */
    private $validate = true;

    /**
     * Whether to use GET instead of POST.
     *
     * @var bool
     */
    private $get = false;

    /**
     * The Twig template to use for the form. Must be form.twig or a child.
     *
     * @var string
     */
    private $template = 'form.twig';

    /**
     * The form fields in the form (an array of MyRadioFormField objects).
     *
     * @var array
     */
    private $fields = [];

    /**
     * The title of the page (the human readable name).
     *
     * @var string
     */
    private $title = null;

    /**
     * The subtitle of the page (a smaller human readable description).
     *
     * @var string
     */
    private $subtitle = null;

    /**
     * Logging output.
     *
     * @var array
     */
    private $debug_log = [];

    /**
     * Enable recaptcha requirement.
     *
     * @var bool
     */
    private $captcha = false;

    /**
     * Fields that cannot be edited by params.
     *
     * @var array
     */
    private $restricted_fields = ['name', 'module', 'action', 'fields', 'restricted_fields', 'debug_log'];

    /**
     * Creates a new MyRadioForm object with the given parameters.
     *
     * @param string $name   The name/id of the form
     * @param string $module The module the form submits to
     * @param string $action The action the form submits to
     * @param array  $params One or more of the following additional settings<br>
     *                       debug - Verbose logging output - default false<br>
     *                       classes - An array of additional classes to apply to the form - default empty<br>
     *                       validate - Whether to validate the field input client-side - default true<br>
     *                       get - Whether to use the GET submission method - default false<br>
     *                       template - The Twig template to use for the form - default form.twig<br>
     *                       title - Form Title<br>
     *                       subtitle - Form Subtitle<br>
     *                       captcha - Whether to require a captcha for this form - default false
     *
     * @throws MyRadioException Thrown on failure of a sanity check
     */
    public function __construct($name, $module, $action, $params = [])
    {
        //Sanity check - does the target exist?
        if (!CoreUtils::isValidController($module, $action)) {
            throw new MyRadioException('The Module/Action target of this MyRadioForm is invalid.');
        }
        //Set essential parameters
        $this->name = $name;
        $this->module = $module;
        $this->action = $action;

        //Check all optional parameters
        foreach ($params as $k => $v) {
            //Sanity checks - is this a valid parameter and is it not blacklisted?
            if (isset($this->$k) === false && @$this->$k !== null) {
                throw new MyRadioException('Tried to set MyRadioForm parameter '.$k.' but it does not exist.');
            }
            if (in_array($k, $this->restricted_fields)) {
                throw new MyRadioException('Tried to set MyRadioForm parameter '.$k.' but it is not editable.');
            }
            $this->$k = $v;
        }
    }

    /**
     * Changes the template to use when rendering.
     *
     * @todo  Check if template exists first
     *
     * @param string $template The path to the template, relative to Templates
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Update the title of the form.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Update the subtitle of the form.
     *
     * @param string $subtitle
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    /**
     * Adds a new MyRadioFormField to this MyRadioForm. You should initialise a new MyRadioFormField and pass the object
     * straight into the parameter of this method.
     *
     * @param MyRadioFormField $field The new MyRadioFormField to add to this MyRadioForm
     *
     * @return MyRadioForm
     *
     * @throws MyRadioException Thrown if there are duplicate fields with the same name
     */
    public function addField(MyRadioFormField $field)
    {
        //Sanity check - is this name in use
        foreach ($this->fields as $f) {
            if ($f->getName() === $field->getName()) {
                throw new MyRadioException('Tried to create a duplicate MyRadioFormField '.$f->getName());
            }
        }
        $this->fields[] = $field;

        return $this;
    }

    /**
     * Allows you to update a MyRadioFormField contained within this object with a new value to be used when rendering.
     *
     * @param string $fieldname The unique name of the MyRadioFormField to edit
     * @param mixed  $value     The new value of the MyRadioFormField. The type depends on the MyRadioFormField type
     *
     * @throws MyRadioException When trying to update a MyRadioFormField that is not attached to this MyRadioForm
     */
    public function setFieldValue($fieldname, $value)
    {
        $name = explode('.', $fieldname)[0];
        foreach ($this->fields as $k => $field) {
            if ($field->getName() === $name) {
                $this->fields[$k]->setValue($value, $fieldname);

                return $this;
            }
        }
        throw new MyRadioException('Cannot set value for field '.$fieldname.' as it does not exist.');

        return $this;
    }

    /**
     * Sets this MyRadioForm as an editing form - it will take existing values and render them for editing and updating.
     *
     * This methods sets all TYPE_FILE fields to not required - it is assumed that they are not needed for editing.
     *
     * @param mixed $ident  Usually a primary key, something unique that the receiving controller will use to know
     *                      which instance of an entry is being updated
     * @param array $values A key=>value array of input names and their values. These will literally be sent to
     *                      setFieldValue iteratively
     * @param string action If set, will replace the default Form action.
     *
     * Note: This method should only be called once in the object's lifetime
     */
    public function editMode($ident, $values, $action = null)
    {
        $this->addField(new MyRadioFormField('myradiofrmedid', MyRadioFormField::TYPE_HIDDEN, ['value' => $ident]));

        foreach ($values as $k => $v) {
            $this->setFieldValue($k, $v);
        }

        if ($action !== null) {
            $this->action = $action;
        }

        //File fields are not required when editing.
        foreach ($this->fields as $f => $v) {
            if ($v->getType() === MyRadioFormField::TYPE_FILE) {
                $this->fields[$f]->setRequired(false);
            }
            if ($v->getType() === MyRadioFormField::TYPE_CHECK && isset($values[$v->getName()])) {
                $this->fields[$f]->setOptions(['checked' => $values[$v->getName()]]);
            }
        }

        return $this;
    }

    /**
     * Sets the values of this form to the given data. Note that this does not set the other fields needed to make it
     * an "edit" form - if you want that, you likely want {@link editMode}.
     * @param $values array
     * @return self
     */
    public function setValues($values)
    {
        foreach ($values as $k => $v) {
            if ($k === 'id') {
                // You probably don't want this.
                continue;
            }
            $this->setFieldValue($k, $v);
        }
        return $this;
    }

    /**
     * Renders a page using the template engine.
     *
     * @param array $frmcustom An optional array of custom fields to send to the Renderer. Useful when using a custom
     *                         template which needs additional data.
     */
    public function render($frmcustom = [])
    {
        /*
         * Prevent XSRF attacks with this token - if this isn't present or is
         * different, then the request is invalid.
         */
        if (!isset($_SESSION['myradio-xsrf-token'])) {
            $_SESSION['myradio-xsrf-token'] = bin2hex(openssl_random_pseudo_bytes(128));
        }
        $this->addField(new MyRadioFormField(
            '__xsrf-token',
            MyRadioFormField::TYPE_HIDDEN,
            ['value' => $_SESSION['myradio-xsrf-token']]
        ));

        /*
         * If we need to do a captcha, load the requirements
         */
        if ($this->captcha) {
            $captcha = '<div class="g-recaptcha" data-sitekey="'.Config::$recaptcha_public_key.'"></div>'
                .'<script src="https://www.google.com/recaptcha/api.js"></script>';
        } else {
            $captcha = null;
        }

        $fields = [];
        $redact = [];
        foreach ($this->fields as $field) {
            $fields[] = $field->render();
            /*
             * Password fields should be redacted from any
             * logging output. Printing request data should use
             * CoreUtils::getRequestInfo
             */
            if ($field->getType() === MyRadioFormField::TYPE_PASSWORD
                or $field->getRedacted()
            ) {
                $redact[] = $this->getPrefix().$field->getName();
            }
        }

        $twig = CoreUtils::getTemplateObject()->setTemplate($this->template)
                ->addVariable('frm_name', $this->name)
                ->addVariable('frm_classes', $this->getClasses())
                ->addVariable('frm_action', URLUtils::makeURL($this->module, $this->action))
                ->addVariable('frm_method', $this->get ? 'get' : 'post')
                ->addVariable('title', isset($this->title) ? $this->title : $this->name)
                ->addVariable('subtitle', isset($this->subtitle) ? $this->subtitle : '')
                ->addVariable('serviceName', isset($this->module) ? $this->module : $this->name)
                ->addVariable('frm_fields', $fields)
                ->addVariable('redact', $redact)
                ->addVariable('captcha', $captcha)
                ->addVariable('frm_custom', $frmcustom);
        $twig->render();
    }

    /**
     * Returns a space-seperated String of classes applying to this MyRadioForm, ready to render.
     *
     * @return string a space-seperated list of classes
     */
    private function getClasses()
    {
        $classes = 'myradiofrm';
        foreach ($this->classes as $class) {
            $classes .= " $class";
        }

        return $classes;
    }

    /**
     * Get the field name prefix.
     */
    private function getPrefix()
    {
        return $this->name.'-';
    }

    /**
     * Processes data submitted from this MyRadioForm, returning an Array of the values.
     *
     * @return array An array of form data that was submitted using this form definition
     *               or false if a captcha was requested and is incorrect.
     */
    public function readValues()
    {
        CoreUtils::checkUploadPostSize();

        //If there was a captcha, verify it
        if ($this->captcha) {
            $valid = AuthUtils::verifyRecaptcha($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
            if ($valid !== true) {
                return false;
            }
        }

        $return = [];
        foreach ($this->fields as $field) {
            $value = $field->readValue($this->getPrefix());
            if ($field->getRequired() && empty($value) && $value !== 0) {
                throw new MyRadioException(
                    'Field '.$field->getName().' is required but has not been set.',
                    400
                );
            }
            $return[$field->getName()] = $value;
        }
        //Edit Mode requests
        if (isset($_REQUEST[$this->getPrefix().'myradiofrmedid'])) {
            $tempID = $_REQUEST[$this->getPrefix().'myradiofrmedid'];
            $return['id'] = is_numeric($tempID) ? (int) $tempID : $tempID;
        }
        //XSRF check
        if (!isset($_SESSION['myradio-xsrf-token'])
            || $_REQUEST[$this->getPrefix().'__xsrf-token'] !== $_SESSION['myradio-xsrf-token']
        ) {
            throw new MyRadioException('Session expired (Invalid token). Please refresh the page.', 401);
        }

        return $return;
    }
}
