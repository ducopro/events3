<?php

class IdfixStorage extends Events3Module
{
    private $oIdfix, $oDb;


    /**
     * Create references to modules we need
     * 
     * @return void
     */
    public function Events3PreRun()
    {
        $this->oIdfix = $this->load('Idfix');
        $this->oDb = $this->load('Database');
    }

    /**
     * Save a full record, update if needed
     * 
     * @param mixed $aFields
     * @return
     */
    public function SaveRecord($aFields)
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        $cTableSpace = $this->GetTableSpaceName();
        $aFields['UidChange'] = 0;
        $aFields['TSChange'] = time();
        // Store all the NON-sql columns in the data field
        $aFields = $this->SavePostProcess($aFields);

        // Trigger the correcte event
        $this->Idfix->Event('SaveRecord', $aFields);

        if (isset($aFields['MainID']) and $aFields['MainID'])
        {
            $iRetval = (integer)$aFields['MainID'];
            unset($aFields['MainID']);

            $this->oDb->Update($cTableSpace, $aFields, 'MainID', $iRetval);

        } else
        {
            $aFields['TSCreate'] = time();
            $aFields['UidCreate'] = 0;
            $iRetval = $this->oDb->Insert($cTableSpace, $aFields);
        }
        $this->IdfixDebug->Profiler(__method__, 'stop');
        return $iRetval;

    }
    public function LoadRecord($iMainId, $bCache = true)
    {

        $cConfigName = $this->oIdfix->cConfigName;

        // Static caching
        static $aStaticCache = array();
        if ($bCache and isset($aStaticCache[$cConfigName][$iMainId]))
        {
            return $aStaticCache[$cConfigName][$iMainId];
        }

        $this->IdfixDebug->Profiler(__method__, 'start');

        // Get tablespace and fetch data
        $cTableSpace = $this->GetTableSpaceName();
        $sql = "SELECT * FROM {$cTableSpace} WHERE MainID = " . intval($iMainId);
        $aDataRow = $this->oDb->DataQuerySingleRow($sql);
        $aDataRow = $this->LoadPostProcess($aDataRow);

        // Change it through the event system
        $this->Idfix->Event('LoadRecord', $aDataRow);

        // Store static cached data
        $aStaticCache[$cConfigName][$iMainId] = $aDataRow;
        $this->IdfixDebug->Profiler(__method__, 'stop');
        return $aDataRow;

    }
    public function LoadAllRecords($iTypeId = null, $iParentId = 0, $cOrder = 'Weight', $aWhere = array(), $cLimit = '')
    {
        $this->IdfixDebug->Profiler( __METHOD__, 'start');
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

        $aData = $this->oDb->DataQuery($cSql);
        // Postprocess the rows
        foreach ($aData as $iRowID => $aRow)
        {
            $iMainId = $aRow['MainID'];
            $aReturn[$iMainId] = $this->LoadPostProcess($aRow);
        }
        $this->IdfixDebug->Profiler( __METHOD__, 'stop');
        return $aReturn;
    }

    /**
     * Check if all the datatables are present
     * 
     * @return void
     */
    public function check()
    {
        $this->IdfixDebug->Profiler( __METHOD__, 'start');
        // Check the base ifdfix table
        $bIdFixIsThere = (count($this->oDb->ShowTables('idfix')) == 1);
        if (!$bIdFixIsThere)
        {
            $cSql = $this->GetIdfixTableSql();
            $this->oDb->Query($cSql);
            echo 'idfix not there';

        }
        // Than check the configuration table
        $cTable = $this->GetTableSpaceName();
        $bTableIsThere = (count($this->oDb->ShowTables($cTable)) == 1);
        if (!$bTableIsThere)
        {
            $this->oDb->Query("CREATE TABLE {$cTable} LIKE idfix");
        }
        $this->IdfixDebug->Profiler( __METHOD__, 'stop');
    }


    /**
     * Return the name of the table that is used for storage
     * of this configurations dataset
     * 
     * @return
     */
    public function GetTableSpaceName()
    {
        return $this->oIdfix->aConfig['tablespace'];
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
        $this->IdfixDebug->Profiler( __METHOD__, 'start');
        $aProps = (array )unserialize($aRow['data']);
        $aRow += $aProps;
        unset($aRow['data']);
        $this->IdfixDebug->Profiler( __METHOD__, 'stop');
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
        $this->IdfixDebug->Profiler( __METHOD__, 'start');
        $cTableSpace = $this->GetTableSpaceName();
        $aFieldList = $this->oDb->ShowColumns($cTableSpace);
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
        $this->IdfixDebug->Profiler( __METHOD__, 'stop');
        return $aRow;
    }
}
