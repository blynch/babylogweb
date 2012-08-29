<?php

	class Context {

		private $config;
		private $requestURI;
		private $requestRoot;
		private $path;
		private $log;
		private $smarty;
		private $method;
		private $options;
		private $identifier;
		private $data;
		private $eventId;

		function __construct() {
			$config = array();
			$requestURI = "";
			$options = array();
		}

  		public function __get($property) {
		    if (property_exists($this, $property)) {
		      return $this->$property;
		    }
  		}

  		public function __set($property, $value) {
		    if (property_exists($this, $property)) {
		      $this->$property = $value;
		    }
		}
		
		
	}

?>