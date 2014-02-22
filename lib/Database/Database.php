<?php

/**
 * Database abstraction layer module
 */
class Database extends Events3Module
{
    // Reference to the PDO instance
    private $pdo = null;

    function Events3ConfigInit(&$aConfig)
    {
        $key = 'DatabasePDOConnectionString';
        if (!isset($aConfig[$key])) {
            $aConfig[$key] = 'mysql:host=Hostname;dbname=databasename';
        }
        $connect = $aConfig[$key];

        $key = 'DatabasePDOUserName';
        if (!isset($aConfig[$key])) {
            $aConfig[$key] = '';
        }
        $user = $aConfig[$key];

        $key = 'DatabasePDOUserPassword';
        if (!isset($aConfig[$key])) {
            $aConfig[$key] = '';
        }
        $pass = $aConfig[$key];

        // Let's instantiate the PDO instance
        // Note that this event is raised from the prerun event
        // of the config module
        try {
            // Create a persistent connection
            $this->pdo = new PDO($connect, $user, $pass, array(PDO::ATTR_PERSISTENT => true));
            $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        } catch (exception $e) {
            echo 'PDO EXTENSION PROBABLY NOT AVAILABLE';
        }
    }

    /**
     * Public Interface
     */

     public function Query( $cSql ) {
        return $this->_query($cSql);
     } 
     
     public function DataQuery($cSql, $aParams = array()) {
        $return = array();
        if( $stmt = $this->_dataquery($cSql, $aParams)) {
            foreach( $stmt as $row ) {
                $return[] = $row;
            }
        }
        return $return;
     }
     public function DataQuerySingleRow( $cSql, $aParams = array()) {
        $set = $this->DataQuery($cSql, $aParams);
        return (array) array_shift($set);
     }
     public function DataQuerySingleValue($cSql, $aParams = array()) {
        $row = $this->DataQuerySingleRow($cSql, $aParams);
        return array_shift($row);
     }
     
     public function ShowTables( $cWildCard = '') {
        $return = array();
        $cSql = 'SHOW TABLES';
        
        if($cWildCard) {
            $cSql .= " LIKE '{$cWildCard}'";
        }
        
        $list = $this->DataQuery($cSql);
        foreach($list as $row) {
            $cTableName = $row[0];
            $return[ $cTableName ] = $cTableName;
        }
        return $return;
     }
     public function CountTables() {
        return (integer) count($this->ShowTables());
     }

     public function ShowColumns( $cTableName ) {
        $return = array();
        $list = $this->DataQuery('SHOW FULL COLUMNS FROM '.$cTableName );
        foreach($list as $row) {
            $cColumnName = $row['Field'];
            $return[ $cColumnName ] = $row;
        }
        return $return;
     }
     public function CountColumns($cTableName) {
        return (integer) count($this->ShowColumns($cTableName));
     }

     public function Insert($cTableName, $aFields) {
        if (!$this->pdo) return 0;
        $cSql = 'INSERT INTO ' . $cTableName . ' (';
        $cSql .= implode(',', array_keys($aFields)) . ') VALUES (';
        $cSql .= substr( str_repeat(',?', count($aFields)), 1) . ')';
        $this->_dataquery( $cSql, $aFields);
        return $this->pdo->lastInsertId();
     }

     public function Update($cTableName, $aFields, $cKeyField, $cKeyValue ) {
        $cSql = 'UPDATE ' . $cTableName . ' SET ';
        foreach($aFields as $cFieldName => $xFieldValue) {
            $cSql .= "{$cFieldName}=?,";
        }
        // Strip last separator
        $cSql = trim($cSql, ',');
        $cSql .= " WHERE {$cKeyField} = ?";
        // Build parameter list
        $aParams = array_values($aFields);
        $aParams[] = $cKeyValue;
        // And send it to the SQL engine
        $this->_dataquery( $cSql, $aParams);
     }

     public function CountRecords($cTableName) {
         if (!$this->pdo) return 0;
         
         $cSql = "SELECT Count(*) FROM {$cTableName}";
         $stmt = $this->_dataquery($cSql);
         $aRow = $stmt->fetch(PDO::FETCH_BOTH);
         return (integer) @$aRow[0];
     }     

     
     /**
      * Use this function as a simple way to get a statement
      * object from PDO. This is a wrapper to use the
      * query parameters in a neat way.
      * 
      * @param string $cSql
      * @return PdoStatement Object or NULL if there is no database connection
      */
     private function _dataquery( $cSql, $aParams = array() ) {
       if ($this->pdo) {
           $stmt = $this->pdo->prepare( $cSql );
           $stmt->execute( array_values( $aParams ) );
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
     private function _query( $cSql ) {
       if ($this->pdo) {
         return $this->pdo->exec( $cSql ); 
       }
       return null;
     }


}