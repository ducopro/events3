<?php

class IdfixStorage extends Events3Module
{


    /**
     * Event handler that is fired each time a query is send to the database
     * 
     * @param mixed $aPack
     * @return void
     */
    public function Events3ProfileQuery($aPack) {
      $this->IdfixDebug->Debug(__method__, $aPack);    
    }

    /**
     * Remove record from the datastore
     * 
     * @param mixed $iMainId
     * @return
     */
    public function DeleteRecord($iMainId) {
        $cTableSpace = $this->GetTableSpaceName();
        $sql = "DELETE FROM {$cTableSpace} WHERE MainID = " . intval($iMainId);
        return (integer) $this->Database->Query($sql);
    }
    /**
     * Save a full record, update if needed
     * 
     * @param mixed $aFields
     * @return
     */
    public function SaveRecord($aFields)
    {
        $cTableSpace = $this->GetTableSpaceName();
        $aFields['TSChange'] = time();
        
        // Call a hook for postprocessing the values
        $this->Idfix->Event('SaveRecord', $aFields);
        
        // Store all the NON-sql columns in the data field
        $aFields = $this->SavePostProcess($aFields);

        // Trigger the correcte event
        $this->Idfix->Event('SaveRecord', $aFields);

        if (isset($aFields['MainID']) and $aFields['MainID'])
        {
            $iRetval = (integer)$aFields['MainID'];
            unset($aFields['MainID']);

            $this->Database->Update($cTableSpace, $aFields, 'MainID', $iRetval);

        } else
        {
            $aFields['TSCreate'] = time();
            $iRetval = $this->Database->Insert($cTableSpace, $aFields);

        }
        return $iRetval;

    }
    public function LoadRecord($iMainId, $bCache = true)
    {

        $cConfigName = $this->Idfix->cConfigName;

        // Static caching
        static $aStaticCache = array();
        if ($bCache and isset($aStaticCache[$cConfigName][$iMainId]))
        {
            return $aStaticCache[$cConfigName][$iMainId];
        }


        // Get tablespace and fetch data
        $cTableSpace = $this->GetTableSpaceName();
        $sql = "SELECT * FROM {$cTableSpace} WHERE MainID = " . intval($iMainId);
        
        $aDataRow = $this->Database->DataQuerySingleRow($sql);

        $aDataRow = $this->LoadPostProcess($aDataRow);

        // Change it through the event system
        $this->Idfix->Event('LoadRecord', $aDataRow);

        // Store static cached data
        $aStaticCache[$cConfigName][$iMainId] = $aDataRow;
        return $aDataRow;

    }
    public function LoadAllRecords($iTypeId = null, $iParentId = 0, $cOrder = 'Weight', $aWhere = array(), $cLimit = '')
    {
        $aReturn = array();
        $cTableSpace = $this->GetTableSpaceName();
        $cSql = "SELECT * FROM {$cTableSpace}";

        // Build dynamic where clauses
        if (is_string($iParentId) and $iParentId)
        {
            $aWhere[] = "ParentID IN ( {$iParentId} )";
        } elseif (is_numeric($iParentId))
        {
            $aWhere[] = 'ParentID = ' . $iParentId;
        }

        if (!is_NULL($iTypeId))
        {
            $aWhere[] = 'TypeID = ' . $iTypeId;
        }


        $cWhereClause = implode(' AND ', $aWhere);
        if ($cWhereClause)
        {
            $cSql .= ' WHERE ' . $cWhereClause;
        }

        if ($cOrder)
        {
            $cSql .= ' ORDER BY ' . $cOrder;
        }

        if ($cLimit)
        {
            $cSql .= ' LIMIT ' . $cLimit;
        }

        $aData = $this->Database->DataQuery($cSql);

        // Postprocess the rows
        foreach ($aData as $iRowID => $aRow)
        {
            $iMainId = $aRow['MainID'];
            $aReturn[$iMainId] = $this->LoadPostProcess($aRow);
        }
        return $aReturn;
    }

    public function CountRecords($iTypeID, $iParentID = null)
    {
        $cTableSpace = $this->GetTableSpaceName();
        $cSql = "SELECT count(*) FROM {$cTableSpace} WHERE TypeID = {$iTypeID}";
        if (!is_null($iParentID))
        {
            $cSql .= " AND ParentID = {$iParentID}";
        }

        $return = $this->Database->DataQuerySingleValue($cSql);

        return $return;
    }

    /**
     * Check if all the datatables are present
     * 
     * @return void
     */
    public function check()
    {
        $bIdFixIsThere = (count($this->Database->ShowTables('idfix')) == 1);

        if (!$bIdFixIsThere)
        {
            $cSql = $this->GetIdfixTableSql();

            $this->Database->Query($cSql);

            //echo 'idfix not there';

        }
        // Than check the configuration table
        $cTable = $this->GetTableSpaceName();

        $bTableIsThere = (count($this->Database->ShowTables($cTable)) == 1);

        if (!$bTableIsThere)
        {
            $this->Database->Query("CREATE TABLE {$cTable} LIKE idfix");
        }
    }


    /**
     * Return the name of the table that is used for storage
     * of this configurations dataset
     * 
     * @return
     */
    public function GetTableSpaceName()
    {
        return $this->Idfix->aConfig['tablespace'];
    }

    /**
     * Show all information about idfix table columns
     * 
     * @return
     */
    public function GetIdfixColumns()
    {
        static $cache = null;
        if (!is_null($cache))
        {
            return $cache;
        }

        $cache = $this->Database->ShowColumns('idfix');

        return $cache;
    }


    /**
     * Get the basic sql code to create an idfix table
     * 
     * @return string SQL code
     */
    private function GetIdfixTableSql()
    {
        return "CREATE TABLE IF NOT EXISTS `idfix` (
                  `MainID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `TypeID` int(10) unsigned DEFAULT NULL,
                  `ParentID` int(10) unsigned DEFAULT NULL,
                  `UidCreate` int(10) unsigned DEFAULT NULL,
                  `UidChange` int(10) unsigned DEFAULT NULL,
                  `TSCreate` int(10) unsigned DEFAULT NULL,
                  `TSChange` int(10) unsigned DEFAULT NULL,
                  `SubTypeID` int(10) unsigned DEFAULT NULL,
                  `RefID` int(10) unsigned DEFAULT NULL,
                  `Weight` int(11) DEFAULT NULL,
                  `Id` varchar(255) DEFAULT '',
                  `Name` varchar(255) DEFAULT '',
                  `Description` varchar(255) DEFAULT '',
                  `Bool_1` tinyint(3) unsigned DEFAULT '0',
                  `Bool_2` tinyint(3) unsigned DEFAULT '0',
                  `Char_1` varchar(255) DEFAULT '',
                  `Char_2` varchar(255) DEFAULT '',
                  `Int_1` int(11) DEFAULT NULL,
                  `Int_2` int(11) DEFAULT NULL,
                  `Text_1` text,
                  `Text_2` text,
                  `data` longblob,
                  PRIMARY KEY (`MainID`),
                  KEY `main` (`TypeID`,`SubTypeID`),
                  KEY `parent` (`ParentID`),
                  KEY `ref` (`RefID`)
                )";
    }

    /**
     * If a row is loaded from the table we need to decode the blob
     * into vitual fields.
     * 
     * @param mixed $aRow
     * @return
     */
    private function LoadPostProcess($aRow)
    {
        $aProps = (array )unserialize($aRow['data']);
        $aRow += $aProps;
        unset($aRow['data']);
        // Add reference to the current configuaration
        $aRow['_config'] = $this->Idfix->cConfigName;
        return $aRow;
    }

    /**
     * Postprocess a row that we need to store in the idfix table
     * Every field we don't have as a MySql column
     * is stored in a blob 
     * 
     * @param array $aRow Record to store in the idfix table
     * @return array postprocessed row
     */
    private function SavePostProcess($aRow)
    {
        $cTableSpace = $this->GetTableSpaceName();

        $aFieldList = $this->Database->ShowColumns($cTableSpace);

        $aProps = array();

        // Check all fields. If it's a real field, save it.
        // If it's not a real field add it to the property list
        // and remove it from the fieldlist
        foreach ($aRow as $cFieldName => $xFieldValue)
        {
            if (!isset($aFieldList[$cFieldName]))
            {
                // It's a property!!!!
                $aProps[$cFieldName] = $xFieldValue;
                unset($aRow[$cFieldName]);
            }
        }
        // Now store the serialized version in the data element
        $aRow['data'] = serialize($aProps);
        return $aRow;
    }


}
