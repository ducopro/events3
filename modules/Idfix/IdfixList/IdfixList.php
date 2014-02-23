<?php

class IdfixList extends Events3Module
{

    private $oIdfix, $oIdfixStorage, $oDatabase;

    public function Events3IdfixActionList(&$output)
    {
        /* @var $this->oIdfix Idfix*/
        $this->oIdfix = $this->load('Idfix');
        /* @var $this->oIdfixStorage IdfixStorage*/
        $this->oIdfixStorage = $this->load('IdfixStorage');
        /* @var $this->oIdfixDatabase IdfixDatabase*/
        $this->oDatabase = $this->load('Database');

        $aTemplateVars = array();

        // Get the title
        $cHook = 'ActionListTitle';
        $aData = array();
        $this->oIdfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->oIdfix->RenderTemplate($cHook, $aData);
        // Get the Breadcrumb trail
        $cHook = 'ActionListBreadcrumb';
        $aData = array();
        $this->oIdfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->oIdfix->RenderTemplate($cHook, $aData);
        // Get the buttonbar
        $cHook = 'ActionListButtonbar';
        $aData = array();
        $this->oIdfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->oIdfix->RenderTemplate($cHook, $aData);
        // Get the grid
        $cHook = 'ActionListMain';
        $aData = array();
        $this->oIdfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->oIdfix->RenderTemplate($cHook, $aData);
        // Get the pager
        $cHook = 'ActionListPager';
        $aData = array();
        $this->oIdfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->oIdfix->RenderTemplate($cHook, $aData);

        // Put them in the template
        $output = $this->oIdfix->RenderTemplate('ActionList', $aTemplateVars);
    }


    public function Events3IdfixActionListTitle(&$aTitle)
    {
        $aTitle['cTitle'] = $this->oIdfix->CleanOutputString($this->oIdfix->aConfig['title']);
        $aTitle['cDescription'] = $this->oIdfix->CleanOutputString($this->oIdfix->aConfig['description']);
    }

    public function Events3IdfixActionListBreadcrumb(&$aData)
    {
        $aData['aBreadcrumb'] = array( //
            'Home' => $this->oIdfix->GetUrl($this->oIdfix->cConfigName), //
            'Level 1' => $this->oIdfix->GetUrl($this->oIdfix->cConfigName), //
            'Level 2' => $this->oIdfix->GetUrl($this->oIdfix->cConfigName), //
            'Level 3' => $this->oIdfix->GetUrl($this->oIdfix->cConfigName), //
            'Level 4' => $this->oIdfix->GetUrl($this->oIdfix->cConfigName), //
            );
    }
    public function Events3IdfixActionListMain(&$aData)
    {
        // let's grab the configuration settings and get only the part
        // we need now, the specifics for this table
        $aConfig = $this->oIdfix->aConfig;
        $cTable = $this->oIdfix->cTableName;
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
        $aDisplayDataset = $this->GetDisplayDataset($aDataSet, $aColumns, $aTableConfig );
        // Now build the template variables
        $aData['aHead'] = $aColumns;
        $aData['aBody'] = $aDisplayDataset;
    }

    public function Events3IdfixActionListButtonbar(&$aData)
    {
        $aConfig = $this->oIdfix->aConfig;
        $cTable = $this->oIdfix->cTableName;
        $aTableConfig = $aConfig['tables'][$cTable];
        $cTableNameUser = $aTableConfig['title'];
        // Default button for returning to the list
        $aData['aButtonbar']['list'] = array(
            'title' => 'List ' . $cTableNameUser,
            'description' => "View a listing of records in the {$cTableNameUser}.",
            'href' => $this->oIdfix->GetUrl(null, null, null, null, null, 'list'),
            'class' => 'active',
            );

        // Default button for a new record
        $aData['aButtonbar']['new'] = array(
            'title' => 'New ' . $cTableNameUser,
            'description' => "Add a new record to the {$cTableNameUser} and edit it.",
            'href' => $this->oIdfix->GetUrl(null, null, null, null, null, 'edit'),
            'class' => '',
            );
    }

    public function Events3IdfixActionListPager(&$aPager)
    {
        // What is the total number of records???
        $cTableSpace = $this->oIdfixStorage->GetTableSpaceName();
        $iRecordsTotal = $this->oDatabase->CountRecords($cTableSpace);

        // What is the number of records on the page
        $aConfig = $this->oIdfix->aConfig;
        $cTable = $this->oIdfix->cTableName;
        $aTableConfig = $aConfig['tables'][$cTable];
        $iRecordsByPage = (integer)$aTableConfig['pager'];

        // What is the total number of pages
        $iPages = 1;
        if ($iRecordsTotal > $iRecordsByPage and $iRecordsTotal and $iRecordsByPage)
        {
            $iPages = ceil($iRecordsTotal / $iRecordsByPage);
        }

        // What is the current page?
        $iPageCurrent = $this->oIdfix->iObject;
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

    }

    /* Private section for rendering the main list */
    private function GetColumns($aTableConfig)
    {
        $cConfigName = $this->oIdfix->cConfigName;
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
                if (!$this->oIdfix->FieldAccess($cConfigName, $aTableConfig['_name'], $cFieldName, 'view'))
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
        return $aColumns;
    }
    
    /**
     * Postp[roces the array of headers.
     * Trigger an event so we cab change the headers
     * For example if we need sorting. We can make the headers
     * clickable and add an icon for showing the sort order.
     * 
     * @param array $aColumns
     * @return array $aHeaders HTML to render in the column header.
     */
    private function GetColumnsHeader($aColumns)
    {
      $aHeader = $aColumns;
      $this->oIdfix->Event('ListHeader', $aHeader);
      return $aHeader;
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
        $aConfig = $this->oIdfix->aConfig;
        $cTable = $this->oIdfix->cTableName;
        $aTableConfig = $aConfig['tables'][$cTable];
        /* Build TypeID */
        $iTypeID = (integer) $aTableConfig['id'];
        /* Build ParentID */
        $iParentID = $this->oIdfix->iParent;
        /* Build a default Order
           @TODO read the default sort order from the config
         */
        $cOrder = 'Weight';
        /* Build the default where clauses*/
        $aWhere = array();
        /* Build the limit clause as an array for convenience
           of the event handler, remember limit is 0-based*/
        $iPage = $this->oIdfix->iObject;
        $iRecsPerPage = $aTableConfig['pager']; 
        $iStart = ( ($iPage-1)* $iRecsPerPage);
        $aLimit = array( $iStart, $iRecsPerPage);   
        
        // Create a PACKAGE for the eventhandler
        $aPack = array(
          'type'=> $iTypeID ,
          'parent'=> $iParentID ,
          'order'=> $cOrder ,
          'where'=> $aWhere ,
          'limit'=> $aLimit ,
        );
        $this->oIdfix->Event( 'ListDataSet', $aPack);
        
        /* Now get all the values back in variables */
        $iTypeID = $aPack['type'];
        $iParentID = $aPack['parent'];
        $cOrder = $aPack['order'];
        $aWhere = $aPack['where'];
        $cLimit = implode(',',  $aPack['limit']);
        
        $aRawDataSet = $this->oIdfixStorage->LoadAllRecords( $iTypeID, $iParentID, $cOrder, $aWhere, $cLimit );        
        return array();
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
    private function GetDisplayDataSet( $aRawSet, $aColumns, $aTableConfig )
    {
        $aDisplaySet = array();
        foreach($aRawSet as $aRawRow ) {
            $aDisplayRow = array();
            foreach( $aColumns as $cFieldName => $cColumnName ) {
                // What is the value we need displayed
                $xFieldData = $aRawRow[ $cFieldName ];
                // And the matching field configuration?
                $aFieldConfig = $aTableConfig['fields'][$cFieldName];
                // Now it's the time to get the visual
                $aDisplayRow[] = $this->GetDisplayDataCell( $xFieldData, $aFieldConfig);
            }
            $aDisplaySet[] = $aDisplayRow;
        }
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
    private function GetDisplayDataCell( $xFieldData, $aFieldConfig) {
        $aFieldConfig['__RawValue'] = $xFieldData;
        $this->oIdfix->Event('DisplayField', $aFieldConfig);
        return $aFieldConfig['__RawValue'];
    }

}
