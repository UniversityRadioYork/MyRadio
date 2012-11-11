<?php
/**
 * This is the bootstrap for the NIPSWeb Service. It sets up things required only for the NIPSWeb Service itself that aren't
 * needed by the MyURY Core. Currently, this is only an additional autoloader for NIPSWeb Classes
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 111112
 * @package MyURY_NIPSWeb
 */

/**
 * Register a MyURY Autoloader 
 */
spl_autoload_register(function($class){
  $class .= '.php';
  if (file_exists($_SESSION['myury_svc_version_NIPSWeb_path'].'/Classes/'.$class)) {
    require_once $_SESSION['myury_svc_version_NIPSWeb_path'].'/Classes/'.$class;
  }
});