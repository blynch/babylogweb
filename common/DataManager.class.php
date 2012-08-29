<?php


class DataManager {

	private $masterDB;
	private $replicaDB;
	private $cachedTables;
	private $cache;
	private $ctx;
	const EXPIRE_CACHE = 7200;

	// Create the database manager with the context of the application
	function __construct(&$ctx) {
		$this->masterDB = self::getConnection($ctx->config->get("_DB_MASTER"), $ctx);		
		$this->replicaDB = self::getConnection($ctx->config->get("_DB_REPLICA"), $ctx);
		$this->cachedTables = $ctx->config->get("_DB_CACHED_TABLES");
		$this->cache = new CacheManager;
		$this->ctx = $ctx;
	} 

	private static function getConnectionName($conn) {
	    $hostinfo = explode(" ", $conn->host_info);
	    return $hostinfo[0];
	}

	private static function getConnection($dbName, $ctx = false) {
		$dbArray = explode(",", $dbName);
		$conn = new MySQLi($dbArray[2], $dbArray[0], $dbArray[1], $dbArray[3]);
		if(mysqli_connect_errno()) {
			echo "DB Connection error!";
		 	$ctx->log->error("DBERROR", "Could not connect to the database! ".$conn->connect_errno);
		}
		/* change character set to utf8 */
		if (!$conn->set_charset("utf8")) {
		 	echo "DB error could not set charset to utf8!";
		 	$ctx->log->error("DBERROR", "Error loading character set utf8: %s " . $conn->error);
		}
		return $conn;
	}

	public function invalidateCache($id, $table) {
    	$this->$cache->invalidate($id, $table);
  	}

  public function query($query = NULL, $useMaster = true, $options = NULL) {
    if($useMaster === true) {
      $conn = $this->masterDB;
      $master = true;
    }
    else {
      $conn = $this->replicaDB;
      $master = false;
    }

    
    $this->ctx->log->debug("QUERY: $query");


    if((!$master)&&self::isSelect($query) === false) {
      $this->ctx->log->error("Query running on ".self::getConnectionName($conn). " : Redirecting to master " . $query);
      $conn = $this->masterDB;
    }

    $startTime = microtime(true);
    $res = $conn->query($query);
    $endTime = microtime(true);

    if($conn->errno) {
    	// .",".serialize($conn->error.debug_backtrace())
      	$this->ctx->log->error("Error running query (".$query.") :".$conn->errno);
    }

    // return the connection if the result is true 
    // (result of an update or insert)
    $res = $res === true ? $conn : $res;
    return $res;
  }

  private static function isSelect($query = NULL, $userMaster = true, $options = NULL) {
    $query = trim($query);
    $firstSeven = strtoupper(substr($query, 0, 7));

    if (strcmp("SELECT ", $firstSeven) == 0 || strcmp("(SELECT", $firstSeven) == 0) {
      return true;
    }
    return false;
  }

  public function queryGetRows($query = null, $useMaster = false, $options = null) {
    $res = $this->query($query, $useMaster, $options);

    if($res === false) {
      return null;
    }

    $rows = array();
    while( ($row = $res->fetch_assoc()) != null ) {
      array_push($rows, $row);
    }
    // $rows = $res->fetch_all(MYSQLI_ASSOC);
    $res->close();

    return $rows;
  }

  public function queryGetResult($query = null, $useMaster = false, $options = null) {
    $res = $this->query($query, $useMaster, $options);

    if($res === false) {
      return null;
    }

    return $res;

  }
  
  public function queryGetUnbufferedResult($query = null, $conn = false, 
                                          $useMaster = false, $options = null){

    if($conn === false) {
      if($useMaster === true) {
        $conn = $this->masterDB;
        $master = true;
      }
      else {
        $conn = $this->replicaDB;
        $master = false;
      }

      if((!$master)&&self::isSelect($query) === false) {
        Logger::log("DBERROR", "Query running on ".self::getConnectionName($conn). " : Redirecting to master " . $query);
        $conn = $this->masterDB;
      }
    }
        
    Logger::log("DB", "QUERY: $query", "DEBUG");

    $startTime = microtime(true);
    $res = $conn->real_query($query);
    $endTime = microtime(true);

    if($conn->errno) {
      Logger::log("DBERROR", "Error running query (".$query.") :".$conn->errno.",".$conn->error.debug_backtrace());
    }

    // return the connection if the result is true 
    // (result of an update or insert)
    $res = $res === true ? $conn->use_result() : null;
    return $res;
  }

  public function queryGetRow($query = null, $useMaster = false, $options = null) {
    $rows = $this->queryGetRows($query, $useMaster, $options);
    $row = is_array($rows) && count($rows) > 0 ? $rows[0] : null;
    return $row;
  }

  public function processValue($value) {
    $inputValue = "";
    if($value === false || isset($value) === false) 
      $inputValue = "NULL";
    else if(is_numeric($value) === false) {
      $value = $this->escape($value); 
      $inputValue = "'$value'";
    }
    else
      $inputValue = $value;

    return $inputValue;
  }

  public function insertRow($table, $params, $ignore = false, $duplicateKeyValues = false) {

    if(is_array($params) === false) {
      $this->ctx->log->error("DB", "Invalid array for insertRow", "DEBUG");
      return false;
    }
    $params['created_at'] = array("now()");

    $arrayKeys = array_keys($params);
    $query = "INSERT ";
    if($ignore) 
      $query .= "IGNORE ";
    $query .= "INTO $table (";
    $inputParams = "";
    foreach($arrayKeys as $key) {
      $inputParams .= strlen($inputParams) > 0 ? "," : "";
      $inputParams .= $key;
    }
    $query .= $inputParams . ") VALUES (";
    $inputValues = "";
    foreach($params as $value) {
      $inputValues .= strlen($inputValues) > 0 ? "," : "";
      if(is_array($value)&&count($value) > 0) 
        $inputValues .= $value[0];
      else
        $inputValues .= $this->processValue($value);
    }
    $query .= $inputValues.") ";
    if($duplicateKeyValues && is_array($duplicateKeyValues)) {
      $query .= "ON DUPLICATE KEY UPDATE ";
      $additional = "";
      foreach($duplicateKeyValues as $key => $value) {
        $additional .= strlen($additional) > 0 ? "," : "";
        $additional .= "$key = ";
        $additional .= $this->processValue($value);
      }
      $query .= "$additional";
    }
    
     $this->ctx->log->debug("INSERT QUERY: $query");
   
    return $this->query($query); 
  }

  public function updateRow($table, $params, $id, $additional = false) {
    $cachedTables = $this->cachedTables;
    $isCached = in_array($table, $cachedTables);
    if(is_array($params) === false) {
      Logger::log("DB", "Invalid array for updateRow", "DEBUG");
      return false;
    }

    $query = "update $table set ";
    $updates = "";
    foreach($params as $column => $value) {
      $updates .= strlen($updates) > 0 ? "," : "";
      $updates .= "$column = ";
      if(is_array($value)&&count($value) > 0) 
        $updates .= $value[0];
      else
        $updates .= $this->processValue($value);
    }

    $query .= $updates;
    $query .= " where id = $id";
    if($additional) {
      $query .= " $additional";
    }

    $result = $this->query($query);
    if($isCached)
      $this->invalidateCache($id, $table);

    return $result;
  }

  public function updateRowByKey($table, $keyName, $params, $id, $additional = false) {
    $isCached = in_array($table, $this->cachedTables);
    if(is_array($params) === false) {
      Logger::log("DB", "Invalid array for updateRow", "DEBUG");
      return false;
    }

    $query = "update $table set ";
    $updates = "";
    foreach($params as $column => $value) {
      $updates .= strlen($updates) > 0 ? "," : "";
      $updates .= "$column = ";
      if(is_array($value)&&count($value) > 0)
        $updates .= $value[0];
      else
        $updates .= $this->processValue($value);
    }

    $query .= $updates;
    $updateId = $this->processValue($id);
    $query .= " where $keyName = $updateId";
    if($additional) {
      $query .= " $additional";
    }

    $result = $this->query($query);
    if($isCached)
      $this->invalidateCache($id, $table);

    return $result;
  }

  public function updateRows($table, $params, $ids) {
    $isCached = in_array($table, $this->cachedTables);
    if(is_array($params) === false || is_array($ids) === false) {
      $this->ctx->log->error("DB", "Invalid array for updateRow", "DEBUG");
      return false;
    }

    $query = "update $table set ";
    $updates = "";
    foreach($params as $column => $value) {
      $updates .= strlen($updates) > 0 ? "," : "";
      $updates .= "$column = ";
      if(is_array($value)&&count($value) > 0)
        $updates .= $value[0];
      else
        $updates .= $this->processValue($value);
    }

    $query .= $updates;

    $idString = implode(",", $ids);
    $query .= " where id in ($idString)";

    $result = $this->query($query);
    if($isCached) {
      foreach($ids as $id) 
        $this->invalidateCache($id, $table);
    }
    return $result;
  }

  public function getRowsByKey($table, $id, $key = false) {
    $isCached = in_array($table, $this->cachedTables);
    $data = array();
    if($isCached) {
      $cache = CacheUtil::getInstance();
      $cacheData = $cache->getData($id, $table, true);
      if($cacheData&&count($cacheData) > 0)
        return $cacheData;
    }

    if(is_numeric($id) === false) {
      $queryId = $this->escape($id);
      $queryId = "'$queryId'";
    }
    else 
      $queryId = $id;

    $query = "select * from $table where $key = $queryId";
    $rows = $this->queryGetRows($query);

    if(count($rows) > 0) {
      if($isCached) {
        $cache->setData($id, $table, $rows, self::EXPIRE_CACHE);
      }
    }

    return $rows;
  }

  public function getRows($table, $ids, $key = false) {
    $isCached = in_array($table, $this->cachedTables);
    $data = array();
    if(is_array($ids) === false&&is_numeric($ids) === false) {
      return false;
    } 
    $ids = is_array($ids) ? $ids : array($ids);
    $key = $key === false ? "id" : $key;

    if($isCached) {
      $cache = CacheUtil::getInstance();
      $cacheData = $cache->getDataForUsers($ids, $table, true);
      if($cacheData&&count($cacheData) > 0) {
        // knock out the ones we got
        $keys = array_keys($cacheData);
        $ids = array_diff($ids, $keys);
        $data = Application::mergeArrays($data, $cacheData);
      }
    }

    if(count($ids) > 0) {
      $idString = implode(",", $ids);
      $query = "select * from $table where $key in ($idString)";
      $rows = $this->queryGetRows($query);

      $dbData = array();
      if(count($rows) > 0) {
        foreach($rows as $row) {
          $index = $row[$key];
          if($isCached) {
            // Cache the user row
            $cache->setData($index, $table, $row, self::EXPIRE_CACHE);
          }
          $dbData[$index] = $row;
        }
        $data = Application::mergeArrays($data, $dbData);
      }
    }

    return $data;
  }

  public function getVersion($userMaster = true) {
    $query = "SELECT version
                FROM migrations
            ORDER BY version
          DESC LIMIT 1";
    $row = $this->queryGetRow($query, $userMaster);
    return $row['version'];
  }


  public function escape($string) {
    return $this->masterDB->real_escape_string($string);
  }

  public function addQueryParam(&$query, $param) {
  	if(strlen($query) == 0)
  		$query = "where ";
  	else
  		$query .= " and ";
  	$query .= " $param ";
  }

  public function close() {

    $this->masterDB->close();
    $this->replicaDB->close();
  }

  public function __destruct() {
    $this->close();
  }

  public function __clone() {
    trigger_error("Cloning <em>DataManager</em> is forbidden", E_USER_ERROR);
  }

};

?>