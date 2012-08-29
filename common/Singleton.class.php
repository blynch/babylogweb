<?php

abstract class Singleton {

	private static $instance;

	abstract private function initialize($environment = "", $script = false);

	public static function getInstance($environment = "", $script = false) {
	    if(!self::$instance) {
	      self::$instance = new Singleton();
	      self::$instance->initialize($environment,$script);
	    }
	    return self::$instance;
	}


}