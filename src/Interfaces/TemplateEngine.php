<?php

namespace MyRadio\Iface;

/**
 * A standard Interface for the Template Engine Abstractors
 * Allows drop-in replacement of template systems
 *
 * @package MyRadio_Core
 */
interface TemplateEngine extends Singleton
{
    public function addVariable($name, $value);
    public function setTemplate($template);
    public function render();
}
