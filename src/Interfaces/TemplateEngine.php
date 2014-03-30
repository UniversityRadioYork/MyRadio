<?php
/**
 * A standard Interface for the Template Engine Abstractors
 * Allows drop-in replacement of template systems
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyRadio_Core
 */
interface TemplateEngine extends Singleton {
  public function addVariable($name, $value);
  public function setTemplate($template);
  public function render();
}
