<?php

class Logger {

  private static $logLevels = null;
  private static $logFileName = null;

  public static $ANA_TYPE_USER_PROFILE = "userprofile";
  public static $ANA_TYPE_USER_LOCATION = "userlocation";
  public static $ANA_TYPE_USER_ENGAGEMENT = "userengage";
  public static $ANA_TYPE_AD_IMPRESSIONS = "adimpress";
  public static $ANA_TYPE_AD_CLICKTHROUGH = "adclick";
  public static $ANA_TYPE_USER_OPERATION = "useroperation";
  public static $ANA_TYPE_MAINTENANCE_RECORD = "maintrecord";


  private static function getDeveloperName() {

    /**
     * use the process's uid if we are not running from a web request
     */
    if( !isset($_SERVER["HTTP_HOST"]) ) {
      $uid_info = posix_getpwuid(posix_getuid());
      $user = "_script_" . $uid_info['name']; 
      return $user;
    }

    $user = null;
    $http_host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : null;
    $hostname = gethostname();
    $inbound_url = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null;

    if(isset($http_host) && !is_null($http_host) && isset($inbound_url) && !is_null($inbound_url)) {
      if (strlen(stristr($hostname, 'dev')) > 0)
      {
        $url_array = split("\.", $http_host);
        $user = "_" . $url_array[0];
/*
        // You're in dev; lookup the location; the username is the first location
        $url_array = split("/", $inbound_url);
        $user = "_" . $url_array[1];
*/
      }
    }
    return $user;
  }

  public static function log($user, $msg, $level = null) {

    $user = isset($_POST['fb_sig_user']) ? $_POST['fb_sig_user'] : $user;
    if($level != null) {
      if (self::$logLevels == null) {
        $config = Configuration::getInstance();
        self::$logLevels = explode(",", $config->get("_ENABLED_LOG_LEVELS"));
      }
      $found = false;
      for($i = 0; $i < count(self::$logLevels); $i++) {
        $logLevel = trim(self::$logLevels[$i]);
        if(strcmp($level, $logLevel) == 0) {
          $found = true;
          break;
        }
      }
      if($found == false && $user != 521436912) {
        return;
      }
    }
    else {
      $level = "INFO";
    }

    if(self::$logFileName == null) {
      $developer = self::getDeveloperName();
      self::$logFileName =  '/tmp/' .  Env::getApplicationType() . $developer . '.log';
    }
    $backtrace = debug_backtrace();
    $callingFile = ""; $callingLine = ""; $callingFunction = "";
    $contents = date('l dS \of F Y h:i:s A') . ': ' . $level . ': User ' . $user;
    $contents .= ': (';
    $p = $_SERVER['SCRIPT_NAME'];
    if($backtrace != null && count($backtrace) > 1) {
      $f = isset($backtrace[1]['class']) ? $backtrace[1]['class'] : "";
      $l = $backtrace[1]['line'];
      $n = $backtrace[1]['function'];
      $contents .=  $f . ':' . $n . ':' . $l . ':';
    } 
    $msg = preg_replace('/[\x00-\x08\x0B-\x1F]/', ' ', $msg); 
    $contents .= $p . ') :' . $msg ."\n";


    file_put_contents(self::$logFileName,
              $contents,
              FILE_APPEND);
  }
  
  public static function logPageView($user, $page, $params = null){
    if(!is_array($params)){
      $params= array();
    }

    $params["user"] = $user;
    $params["page"] = $page;
    $params["time"] = time();

    self::logForAnalytics(self::$ANA_TYPE_USER_OPERATION, $params);
  }

  public static function addLogLevel($level) {
    if(self::$logLevels == null) {
        $config = Configuration::getInstance();
        self::$logLevels = explode(",", $config->get("_ENABLED_LOG_LEVELS"));
    }
    array_push(self::$logLevels, $level);
  }

  public static function removeLogLevel($level) {
    if(self::$logLevels == null) {
        $config = Configuration::getInstance();
        self::$logLevels = explode(",", $config->get("_ENABLED_LOG_LEVELS"));
    }
    array_splice(self::$logLevels, $level);
  }

  public static function resetLogLevels() {
    self::$logLevels = null;
  }

  public static function setLogFileName($fileName) {
    self::$logFileName = $fileName;
  }

  public static function resetLogFileName() {
    self::$logFileName = null;
  }


};

?>