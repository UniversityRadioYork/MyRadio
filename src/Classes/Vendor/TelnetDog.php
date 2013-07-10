<?php
/***
 * TelnetDog
 * Telnet client in PHP
 *
 * @author William F.
 * @copyright GPL ©2010
 * @version 1.5
 * @package TelnetDog.class
 * @subpackage TelnetDog_FTP.class
*/

class TelnetDog {
 function __construct($h, $p){
  try {
   $this->socket = fsockopen($h, $p);
   $this->host = $h;
   $this->port = $p;

   if(!$this->socket){
    throw new Exception("[TelnetDog] Could not connect to ".$this->host.":".$this->port.".");
   }
  } catch (Exception $ex){
   echo($ex->getMessage());
  } 
 }

 function Close(){
  return fclose($this->socket);
  $this->socket = null;
 }

 function Status(){
  if(!$this->socket){
   return "[TelnetDog] ".$this->host.":".$this->port." - Not connected";
  } else {
   return "[TelnetDog] ".$this->host.":".$this->port." - Connected";
  }
 }

 function Receiving(){
  return "!feof($this->socket)";
 }

 function Execute($c){
  return fputs($this->socket, $c, strlen($c));
 }

 function Write($c){
  return fwrite($this->socket, $c."\n", strlen($c));
 }

 function Get($br){
  if(!$br){
   return fgets($this->socket, 1024);
  } else {
   return fgets($this->socket, 1024)."</br>";
  }
 }
}