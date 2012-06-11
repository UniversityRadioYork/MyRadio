<?php
/**
 * A standard Interface for the Template Engine Abstractors
 * Allows drop-in replacement of template systems
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 */
interface TemplateEngine extends Singleton {
  public function addVariable($name, $value);
  public function setTemplate($template);
  public function render();
}