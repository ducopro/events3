<?php

class IdfixList extends Events3Module
{


    public function Events3IdfixActionList(&$output)
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        // Store the last known page
        $this->Idfix->GetSetLastListPage();

        $aTemplateVars = array();

        // Get the title
        $cHook = 'ActionListTitle';
        $aData = array();
        $this->Idfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->Idfix->RenderTemplate($cHook, $aData);
        // Get the Breadcrumb trail
        $cHook = 'ActionListBreadcrumb';
        $aData = array();
        $this->Idfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->Idfix->RenderTemplate($cHook, $aData);
        // Get the grid
        $cHook = 'ActionListMain';
        $aData = array();
        $this->Idfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->Idfix->RenderTemplate($cHook, $aData);
        // Get the pager
        $cHook = 'ActionListPager';
        $aData = array();
        $this->Idfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->Idfix->RenderTemplate($cHook, $aData);

        // Put them in the template
        $output = $this->Idfix->RenderTemplate('ActionList', $aTemplateVars);
        $this->IdfixDebug->Profiler(__method__, 'stop');
    }


    /**
     * Title and description for this specific table
     * 
     * @param array $aTitle termplate variables
     * @return void
     */
    public function Events3IdfixActionListTitle(&$aTitle)
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        $aConfig = $this->Idfix->aConfig;
        $cTable = $this->Idfix->cTableName;
        $aTableConfig = $aConfig['tables'][$cTable];

        $aTitle['cTitle'] = $aTableConfig['title'];
        $aTitle['cDescription'] = $aTableConfig['description'];
        $aTitle['cIcon'] = $this->Idfix->GetIconHTML($aTableConfig['icon']);
        $this->IdfixDebug->Profiler(__method__, 'stop');
    }


    public function Events3IdfixActionListBreadcrumb(&$aData)
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        $aData['aBreadcrumb'] = array( //
            'Home' => $this->Idfix->GetUrl($this->Idfix->cConfigName), //
            'Level 1' => $this->Idfix->GetUrl($this->Idfix->cConfigName), //
            'Level 2' => $this->Idfix->GetUrl($this->Idfix->cConfigName), //
            'Level 3' => $this->Idfix->GetUrl($this->Idfix->cConfigName), //
            'Level 4' => $this->Idfix->GetUrl($this->Idfix->cConfigName), //
            );
        $this->IdfixDebug->Profiler(__method__, 'stop');
    }
    
    public function Events3IdfixActionListMain(&$aData)
    {
        $this->IdfixDebug->Profiler( __METHOD__, 'start');
        // let's grab the configuration settings and get only the part
        // we need now, the specifics for this table
        $aConfig = $this->Idfix->aConfig;
        $cTable = $this->Idfix->cTableName;
        $aTableConfig = $aConfig['tables'][$cTable];
        // Retrieve a list of columns we need to show
        // Access to each field is checked so we can be sure these
        // fields can be showed
        $aColumns = $this->GetColumns($aTableConfig);
        // Now postprocess the columns to give us a nice tableheader
        $aHeader = $this->GetColumnsHeader($aColumns);
        // Now give us the raw dataset with the values
        // from the Idfix table
        $aDataSet = $this->GetRawDataset();
        // Than postprocess this dataset to provide the
        // exact HTML for display purposes
        $aDisplayDataset = $this->GetDisplayDataset($aDataSet, $aColumns, $aTableConfig);
        // Now build the template variables
        $aData['aHead'] = $aHeader;
        $aData['aBody'] = $aDisplayDataset;
        $this->IdfixDebug->Profiler( __METHOD__, 'stop');
    }


    public function Events3IdfixActionListPager(&$aPager)
    {
        $this->IdfixDebug->Profiler( __METHOD__, 'start');
        // What is the number of records on the page
        $aConfig = $this->Idfix->aConfig;
        $cTable = $this->Idfix->cTableName;
        $aTableConfig = $aConfig['tables'][$cTable];
        $iRecordsByPage = (integer)$aTableConfig['pager'];
        // What is the total number of records???
        $iRecordsTotal = $this->IdfixStorage->CountRecords($aTableConfig['id'], $this->Idfix->iParent);

        // What is the total number of pages
        $iPages = 1;
        if ($iRecordsTotal > $iRecordsByPage and $iRecordsTotal and $iRecordsByPage)
        {
            $iPages = ceil($iRecordsTotal / $iRecordsByPage);
        }

        // What is the current page?
        $iPageCurrent = $this->Idfix->iObject;
        // Check the current page requested. Maybe it is out of bounds??
        $iPageCurrent = ($iPageCurrent > $iPages) ? $iPages : $iPageCurrent;
        $iPageCurrent = ($iPageCurrent < 1) ? 1 : $iPageCurrent;
        // What is the next page
        $iPageNext = min($iPageCurrent + 1, $iPages);
        // What is the previous page
        $iPagePrevious = max($iPageCurrent - 1, 1);

        // Try to create a set of 5 pages before and after the current page
        $iSetSize = 5;
        $iStartSet = max(1, $iPageCurrent - $iSetSize);
        $iStopSet = min($iPages, $iPageCurrent + $iSetSize);

        // Put the information in the pager-array for rendering
        $aPager['iRecordsTotal'] = $iRecordsTotal;
        $aPager['iRecordsPage'] = $iRecordsByPage;
        $aPager['iPageTotal'] = $iPages;
        $aPager['iPageCurrent'] = $iPageCurrent;
        $aPager['iPageNext'] = $iPageNext;
        $aPager['iPagePrev'] = $iPagePrevious;
        $aPager['iSetStart'] = $iStartSet;
        $aPager['iSetStop'] = $iStopSet;

        //$this->IdfixDebug->Debug(__method__, $aPager);
        $this->IdfixDebug->Profiler( __METHOD__, 'stop');

    }

    /* Private section for rendering the main list */
    private function GetColumns($aTableConfig)
    {
        $this->IdfixDebug->Profiler( __METHOD__, 'start');
        $cConfigName = $this->Idfix->cConfigName;
        // initialize Output array
        $aColumns = array();
        // Callback processing
        $aList = $aTableConfig['list'];

        if (is_array($aList))
        {
            foreach ($aList as $cFieldName => $cColumnName)
            {
                // Default fieldname and Columnname
                if (is_numeric($cFieldName))
                {
                    $cFieldName = $cColumnName;
                    $cColumnName = (string )@$aTableConfig['fields'][$cFieldName]['title'];
                }
                // If there are field level permissions, check them and act accordingly
                if (!$this->Idfix->FieldAccess($cConfigName, $aTableConfig['_name'], $cFieldName, 'view'))
                {
                    continue;
                }
                // Check if we have a title
                if (!$cColumnName)
                {
                    $cColumnName = $cFieldName;
                }
                // Now store the values
                $aColumns[$cFieldName] = $cColumnName;
            }
        }
        $this->Idfix->Event('ListColumns', $aColumns);
        $this->IdfixDebug->Profiler( __METHOD__, 'stop');
        return $aColumns;
    }

    /**
     * Postp[roces the array of headers.
     * Trigger an event so we can change the headers
     * For example if we need sorting. We can make the headers
     * clickable and add an icon for showing the sort order.
     * 
     * @param array $aColumns
     * @return array $aHeaders HTML to render in the column header.
     */
    private function GetColumnsHeader($aColumns)
    {
        $this->IdfixDebug->Profiler( __METHOD__, 'start');
        //$aHeader = $aColumns;
        $this->Idfix->Event('ListHeader', $aColumns);
        $this->IdfixDebug->Profiler( __METHOD__, 'stop');
        return $aColumns;
    }

    /**
     * Return the full datset from the idfix table.
     * - Construct the right parameters for the
     * IdfixStorage->LoadAllRecords() API function
     * 
     * @return
     */
    private function GetRawDataset()
    {
        $this->IdfixDebug->Profiler( __METHOD__, 'start');
        $aConfig = $this->Idfix->aConfig;
        $cTable = $this->Idfix->cTableName;
        $aTableConfig = $aConfig['tables'][$cTable];
        /* Build TypeID */
        $iTypeID = (integer)$aTableConfig['id'];
        /* Build ParentID */
        $iParentID = $this->Idfix->iParent;
        /* Build a default Order
        @TODO read the default sort order from the config
        */
        $cOrder = 'Weight';
        /* Build the default where clauses*/
        $aWhere = array();
        /* Build the limit clause as an array for convenience
        of the event handler, remember limit is 0-based*/
        $iPage = $this->Idfix->iObject;
        $iRecsPerPage = $aTableConfig['pager'];
        $iStart = (($iPage - 1) * $iRecsPerPage);
        $aLimit = array($iStart, $iRecsPerPage);

        // Create a PACKAGE for the eventhandler
        $aPack = array(
            'type' => $iTypeID,
            'parent' => $iParentID,
            'order' => $cOrder,
            'where' => $aWhere,
            'limit' => $aLimit,
            );
        $this->Idfix->Event('ListDataSet', $aPack);

        /* Now get all the values back in variables */
        $iTypeID = $aPack['type'];
        $iParentID = $aPack['parent'];
        $cOrder = $aPack['order'];
        $aWhere = $aPack['where'];
        $cLimit = implode(',', $aPack['limit']);

        $this->IdfixDebug->Profiler( __METHOD__ . '::LoadAllRecords', 'start');
        $aRawDataSet = $this->IdfixStorage->LoadAllRecords($iTypeID, $iParentID, $cOrder, $aWhere, $cLimit);
        $this->IdfixDebug->Profiler( __METHOD__ . '::LoadAllRecords', 'stop');
        
        $this->IdfixDebug->Profiler( __METHOD__, 'stop');
        return $aRawDataSet;
    }

    /**
     * Postprocess every row in the raw dataset and extract
     * only the fields we need.
     * Than use the field-classes to get a visual representation
     * 
     * @param mixed $aRawSet
     * @param mixed $aColumns
     * @return
     */
    private function GetDisplayDataSet($aRawSet, $aColumns, $aTableConfig)
    {
        $this->IdfixDebug->Profiler( __METHOD__, 'start');
        $aDisplaySet = array();

        foreach ($aRawSet as $aRawRow)
        {
            $aDisplayRow = array();
            foreach ($aColumns as $cFieldName => $cColumnName)
            {
                // What is the value we need displayed
                $xFieldData = '';
                if (isset($aRawRow[$cFieldName]))
                {
                    $xFieldData = $aRawRow[$cFieldName];
                }

                // And the matching field configuration?
                $aFieldConfig = $aTableConfig['fields'][$cFieldName];
                // Postprocess the configuration
                $aFieldConfig = $this->Idfix->PostprocesConfig( $aFieldConfig, $aRawRow);
                // Now it's the time to get the visual
                $aDisplayRow[] = $this->GetDisplayDataCell($xFieldData, $aFieldConfig);
            }
            $aDisplaySet[] = $aDisplayRow;
        }
        //print_r($aDisplaySet);
        $this->IdfixDebug->Profiler( __METHOD__ , 'stop');
        return $aDisplaySet;
    }

    /**
     * Given a fieldconfiguration and a value
     * return the display
     * 
     * @todo Create eventhandler to return the right value
     * 
     * @param mixed $xFieldData
     * @param mixed $aFieldConfig
     * @return void
     */
    private function GetDisplayDataCell($xFieldData, $aFieldConfig)
    {
        $this->IdfixDebug->Profiler( __METHOD__ , 'start');
        $aFieldConfig['__RawValue'] = $xFieldData;
        $this->Idfix->Event('DisplayField', $aFieldConfig);
        $this->IdfixDebug->Profiler( __METHOD__ , 'stop');
        return $aFieldConfig['__DisplayValue'];
    }

}
