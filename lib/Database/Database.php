<?php

/**
 * Database abstraction layer module
 * 
 * Note:
 * Connect to 127.0.0.1 instead of localhost for extra performance!
 * 
 */
class Database extends Events3Module {

  // Reference to the PDO instance
  private $_pdo = null;

  /**
   * Database::__get()
   * Only used for the PDO object for on demand loading
   * 
   * @param mixed $cModuleName
   * @return
   */
  public function __get($cPdo) {
    if ($cPdo == 'pdo') {
      if (!$this->_pdo) {
        // Try to load the database connection
        try {
          // Only use persistent connections on normal hosting
          $bPersistent = !$this->ev3->GAE_IsPlatform();
          // Create a connection
          $this->_pdo = new PDO($this->connect, $this->user, $this->pass, array(PDO::ATTR_PERSISTENT => $bPersistent, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
          //$this->pdo->exec("SET NAMES 'utf8'");
          $this->_pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
          //$this->log('On demand loading MySql');
          //$this->log(print_r(debug_backtrace(), true));
        }
        catch (exception $e) {
          echo 'PDO EXTENSION PROBABLY NOT AVAILABLE';
        }
      }
      return $this->_pdo;
    }
  }

  /**
   * Simple wrapper around the quote method
   * 
   * @param mixed $cString
   * @return
   */
  public function quote($cString) {
    return $this->pdo->quote($cString);
  }

  function Events3ConfigInit(&$aConfig) {
    $key = 'DatabasePDOConnectionString';
    if (!isset($aConfig[$key])) {
      $aConfig[$key] = 'mysql:host=Hostname;dbname=databasename';
    }
    $this->connect = $aConfig[$key];
    $key = 'DatabasePDOUserName';
    if (!isset($aConfig[$key])) {
      $aConfig[$key] = '';
    }
    $this->user = $aConfig[$key];
    $key = 'DatabasePDOUserPassword';
    if (!isset($aConfig[$key])) {
      $aConfig[$key] = '';
    }
    $this->pass = $aConfig[$key]; // Let's instantiate the PDO instance
    // Note that this event is raised from the prerun event
    // of the config module
    try {
      // Create a persistent connection
      //$this->pdo = new PDO($connect, $user, $pass, array(PDO::ATTR_PERSISTENT => false, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
      //$this->pdo->exec("SET NAMES 'utf8'");
      //$this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }
    catch (exception $e) {
      echo 'PDO EXTENSION PROBABLY NOT AVAILABLE';
    }
  }

  /**
   * Public Interface
   */
  public function Query($cSql) {
    return $this->_query($cSql);
  }

  public function DataQuery($cSql, $aParams = array()) {
    $return = array();
    if ($stmt = $this->_dataquery($cSql, $aParams)) {
      foreach ($stmt as $row) {
        $return[] = $row;
      }
    }

    // Do some explaining
    if (false) {
      $aExplain = array();
      $cSql = 'EXPLAIN ' . $cSql;
      if ($stmt = $this->_dataquery($cSql, $aParams)) {
        foreach ($stmt as $row) {
          $aExplain[] = $row;
        }
      }
      if (count($aExplain) > 0) {
        $this->IdfixDebug->Debug('EXPLAIN', $aExplain);
      }

    }

    return $return;
  }

  public function DataQuerySingleRow($cSql, $aParams = array()) {
    $set = $this->DataQuery($cSql, $aParams);
    return (array )array_shift($set);
  }

  public function DataQuerySingleValue($cSql, $aParams = array()) {
    $row = $this->DataQuerySingleRow($cSql, $aParams);
    return array_shift($row);
  }

  public function ShowTables($cWildCard = '') {
    $return = array();
    $cSql = 'SHOW TABLES';
    if ($cWildCard) {
      $cSql .= " LIKE '{$cWildCard}'";
    }

    $list = $this->DataQuery($cSql);
    foreach ($list as $row) {
      $cTableName = array_shift($row);
      $return[$cTableName] = $cTableName;
    }
    return $return;
  }

  public function CountTables() {
    return (integer)count($this->ShowTables());
  }

  public function ShowColumns($cTableName) {
    $return = array();
    $list = $this->DataQuery('SHOW FULL COLUMNS FROM ' . $cTableName);
    foreach ($list as $row) {
      $cColumnName = $row['Field'];
      $return[$cColumnName] = $row;
    }
    return $return;
  }

  public function CountColumns($cTableName) {
    return (integer)count($this->ShowColumns($cTableName));
  }

  public function Insert($cTableName, $aFields) {
    if (!$this->pdo) return 0;
    $cSql = 'INSERT INTO ' . $cTableName . ' (';
    $cSql .= implode(',', array_keys($aFields)) . ') VALUES (';
    $cSql .= substr(str_repeat(',?', count($aFields)), 1) . ')';
    $this->_dataquery($cSql, $aFields);
    return $this->pdo->lastInsertId();
  }

  public function Update($cTableName, $aFields, $cKeyField, $cKeyValue) {
    $cSql = 'UPDATE ' . $cTableName . ' SET ';
    foreach ($aFields as $cFieldName => $xFieldValue) {
      $cSql .= "{$cFieldName}=?,";
    }
    // Strip last separator
    $cSql = trim($cSql, ',');
    $cSql .= " WHERE {$cKeyField} = ?"; // Build parameter list
    $aParams = array_values($aFields);
    $aParams[] = $cKeyValue; // And send it to the SQL engine
    $this->_dataquery($cSql, $aParams);
  }

  public function CountRecords($cTableName) {
    if (!$this->pdo) return 0;
    $cSql = "SELECT Count(*) FROM {$cTableName}";
    $stmt = $this->_dataquery($cSql);
    $aRow = $stmt->fetch(PDO::FETCH_BOTH);
    return (integer)@$aRow[0];
  }

  /**
   * Simple routine to store a complete table in a txt file
   * Use the lines to create an INSERT INTO ..... statement
   * for recreating the table contents
   * 
   * @param mixed $cFileName
   * @param mixed $cTableName
   * @return void
   */
  public function DumpTableData($cFileName, $cTableName) {
    $cSql = 'SELECT * FROM ' . $cTableName;
    if ($stmt = $this->_dataquery($cSql)) {
      if ($fHandle = fopen($cFileName, 'w')) {
        foreach ($stmt as $row) {
          $outrow = array();
          foreach ($row as $xData) {
            $outrow[] = $this->quote($xData);
          }
          // NOT Allowed on GAE
          //file_put_contents($cFileName, implode(',', $outrow) . "\n", FILE_APPEND);
          fwrite($fHandle, implode(',', $outrow) . "\n");
        }
        fclose($fHandle);
      }
    }
  }

  public function RestoreTableData($cFileName, $cTableName) {
    if ($fHandle = fopen($cFileName, 'r')) {
      $this->Query("TRUNCATE {$cTableName}");
      while ($cLine = fgets($fHandle, 80192)) {
        $cSql = "INSERT INTO {$cTableName} VALUES ({$cLine})";
        $this->Query($cSql);
      }
      fclose($fHandle);
    }
  }

  /**
   * Use this function as a simple way to get a statement
   * object from PDO. This is a wrapper to use the
   * query parameters in a neat way.
   * 
   * @param string $cSql
   * @return PdoStatement Object or NULL if there is no database connection
   */
  private function _dataquery($cSql, $aParams = array()) {
    if ($this->pdo) {
      // Preprocess the data to UTF-8
      //foreach ($aParams as $fieldname => &$data) {
      //    if (is_string($data)) {
      //        $data = utf8_encode($data);
      //    }
      //}
      $this->ProfileQuery();
      $stmt = $this->pdo->prepare($cSql);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute(array_values($aParams));
      $this->ProfileQuery($stmt->queryString, $aParams);
      //$this->IdfixDebug->Debug(__METHOD__, $stmt);
      //$this->IdfixDebug->Debug(__METHOD__, $retval);
      //$this->IdfixDebug->Debug(__METHOD__, $this->pdo);
      return $stmt;
    }
    return null;
  }

  /**
   * Use this to execute 1 sql statement on the database
   * when this statement is NOT returning data
   * 
   * @param string $cSql
   * @return integer number of rows affected or NULL if there is no dattabase connection
   */
  private function _query($cSql) {
    if ($this->pdo) {

      $this->ProfileQuery();
      $return = $this->pdo->exec($cSql);
      $this->ProfileQuery($cSql);
      return $return;
    }
    return null;
  }

  /**
   * Sometimes we just want to know how many queries we send
   * to the database and how much time it took.
   * This function collects all the data and fires the right event.
   * 
   * 
   * @param string $cOp Start or Stop
   * @param string $cSql The SQL we send to the data engine
   * @return void
   */
  private function ProfileQuery($cSql = '', $aParams = array()) {
    static $iStart = 0;
    static $iCum = 0;
    if (!$cSql) {
      $iStart = (float)microtime(true);
    }
    else {
      $iStop = (float)microtime(true);
      $iThroughPut = (float)round(($iStop - $iStart) * 1000, 2);
      $iCum += $iThroughPut; // Create Package
      $aPack = array(
        'time' => $iThroughPut,
        'total' => $iCum,
        'sql' => $cSql,
        'params' => $aParams,
        );
      $ev3 = Events3::GetHandler();
      $ev3->Raise('ProfileQuery', $aPack);
    }
  }

}
