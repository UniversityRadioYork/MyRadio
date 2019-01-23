<?php

namespace MyRadio;

use MyRadio\Config;
use MyRadio\Database;
use MyRadio\MyRadio\AuthUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadio\MyRadioMenu;
use MyRadio\MyRadioException;
use MyRadio\MyRadioError;
use Twig_Loader_Filesystem;
use Twig_Environment;
use Twig_Extension_Debug;

/**
 * Singleton class for the Twig template engine.
 *
 * @depends Config
 */
class MyRadioTwig implements \MyRadio\Iface\TemplateEngine
{
    private $contextVariables = [];
    private $template;
    private $twig;

    /**
     * Cannot be private - parent does not allow it.
     *
     * @todo Better Documentation
     */
    public function __construct()
    {
        $twig_loader = new Twig_Loader_Filesystem(__DIR__.'/../Templates/');
        $this->contextVariables['notices'] = [];
        $this->twig = new Twig_Environment($twig_loader, ['auto_reload' => true]);
        if (Config::$template_debug) {
            $this->twig->addExtension(new Twig_Extension_Debug());
            $this->twig->enableDebug();
        }

        $this->addVariable('memberid', isset($_SESSION['memberid']) ? $_SESSION['memberid'] : 0)
            ->addVariable(
                'impersonatorurl',
                !empty($_SESSION['myradio-impersonating'])
                ? (URLUtils::makeURL('MyRadio', 'impersonate', ['next' => $_SERVER['REQUEST_URI']]))
                : ''
            )
            ->addVariable(
                'impersonator',
                !empty($_SESSION['myradio-impersonating'])
                ? $_SESSION['myradio-impersonating']['name']
                : ''
            )
            ->addVariable('timeslotname', isset($_SESSION['timeslotname']) ? $_SESSION['timeslotname'] : null)
            ->addVariable('timeslotid', isset($_SESSION['timeslotid']) ? $_SESSION['timeslotid'] : null)
            ->addVariable('baseurl', Config::$base_url)
            ->addVariable('websiteurl', Config::$website_url)
            ->addVariable('shortname', Config::$short_name)
            ->addVariable('rewriteurl', Config::$rewrite_url)
            ->addVariable('serviceName', 'MyRadio')
            ->setTemplate('stripe.twig')
            ->addVariable('uri', $_SERVER['REQUEST_URI'])
            ->addVariable('module', empty($GLOBALS['module']) ? Config::$default_module : $GLOBALS['module'])
            ->addVariable('action', empty($GLOBALS['action']) ? Config::$default_action : $GLOBALS['action'])
            ->addVariable('config', Config::getPublicConfig())
            ->addVariable('name', isset($_SESSION['name']) ? $_SESSION['name'] : '');

        if (!empty($GLOBALS['module']) && isset($_SESSION['memberid'])) {
            $this->addVariable('submenu', (new MyRadioMenu())->getSubMenuForUser($GLOBALS['module']))
                ->addVariable('title', $GLOBALS['module']);
        }

        //Make requests override session-set joyrides
        if (!empty($_REQUEST['joyride'])) {
            $this->addVariable('joyride', $_REQUEST['joyride']);
        }

        if (isset($_REQUEST['message'])) {
            $this->addInfo(strip_tags(base64_decode($_REQUEST['message']), '<a>'));
        }
    }

    /**
     * Registers a new variable to be passed to the template.
     *
     * @param string $name  The name of the variable
     * @param mixed  $value The value of the variable - literally any valid type
     *
     * @return \MyRadioTwig This for chaining
     */
    public function addVariable($name, $value)
    {
        /*
         * This is a hack for datatables, as there's no easy way for Twig to know booleans.
         * It's slow.
         * @todo Is there a better way of casting true/false to Yes/No?
         */
        if ($name === 'tabledata') {
            $value = $this->boolParser($value);
        }

        if ($name === 'notices') {
            throw new MyRadioException('Notices cannot be directly set via the Template Engine');
        }
        $this->contextVariables[$name] = $value;

        return $this;
    }

    /**
     * Recursively iterates over an array of any depth, replacing all booleans with "Yes" or "No".
     * Used for the datatable hack.
     *
     * @param array $value
     *
     * @return array
     */
    private function boolParser($value)
    {
        if (!is_array($value)) {
            return $value;
        }
        foreach ($value as $k => $v) {
            if (is_bool($v)) {
                $value[$k] = $v ? 'Yes' : 'No';
            } elseif (is_array($v)) {
                $value[$k] = $this->boolParser($v);
            }
        }

        return $value;
    }

    public function addInfo($message, $icon = 'info-sign')
    {
        $this->contextVariables['notices'][] = ['icon' => $icon, 'message' => $message, 'state' => 'info'];

        return $this;
    }

    public function addError($message, $icon = 'warning-sign')
    {
        $this->contextVariables['notices'][] = ['icon' => $icon, 'message' => $message, 'state' => 'danger'];

        return $this;
    }

    /**
     * Sets the template file to use.
     *
     * @param string $template The template filename
     *
     * @throws MyRadioException If template does not exist
     *
     * @return MyRadioTwig This for chaining
     */
    public function setTemplate($template)
    {
        if (!file_exists(__DIR__.'/../Templates/'.$template)) {
            throw new MyRadioException("Template $template does not exist");
        }

        //Validate template
        try {
            $this->twig->parse($this->twig->tokenize(
                new \Twig_Source(file_get_contents(__DIR__.'/../Templates/'.$template), $template)
            ));

            // the $template is valid
        } catch (Twig_Error_Syntax $e) {
            throw new MyRadioException('Twig Parse Error'.$e->getMessage(), $e->getCode(), $e);
        }

        $this->template = $this->twig->loadTemplate($template);

        return $this;
    }

    /**
     * Renders the template.
     */
    public function render()
    {
        if ((defined('AUTH_SHOWERRORS') && AuthUtils::hasPermission(AUTH_SHOWERRORS))
            || Config::$display_errors
        ) {
            $this->addVariable('phperrors', MyRadioError::$php_errorlist);
            if (isset($_SESSION)) { //Is the DB working?
                $this->addVariable('query_count', Database::getInstance()->getCounter());
            }
        }

        $output = $this->template->render($this->contextVariables);
        if (empty($output)) {
            //That's not right.
            throw new MyRadioException(
                'Failed to render page '
                .'(template '.$this->template->getTemplateName().')',
                500
            );
        }
        echo $output;
    }

    public static function getInstance()
    {
        return new self();
    }
}
