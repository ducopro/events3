<?php

class IdfixList extends Events3Module {


  public function Events3IdfixActionList(&$output) {
    //$this->IdfixDebug->Profiler(__method__, 'start');

    // Check for a valid tablename
    if (!isset($this->Idfix->aConfig['tables'][$this->Idfix->cTableName])) {
      return;
    }

    // Check for view access
    if (!$this->Idfix->Access($this->Idfix->cTableName . '_v')) {
      return;
    }

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
    //$aData = array();
    //$this->Idfix->Event($cHook, $aData);
    $aTemplateVars[$cHook] = $this->Idfix->BreadCrumbs($this->Idfix->iParent);
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
    $output .= $this->Idfix->RenderTemplate('ActionList', $aTemplateVars);
    //$this->IdfixDebug->Profiler(__method__, 'stop');
  }


  /**
   * Title and description for this specific table
   * 
   * @param array $aTitle termplate variables
   * @return void
   */
  public function Events3IdfixActionListTitle(&$aTitle) {
    //$this->IdfixDebug->Profiler(__method__, 'start');
    $aConfig = $this->Idfix->aConfig;
    $cTable = $this->Idfix->cTableName;
    $aTableConfig = $aConfig['tables'][$cTable];

    $aTitle['cTitle'] = $aTableConfig['title'];
    $aTitle['cDescription'] = $aTableConfig['description'];
    $aTitle['cIcon'] = $this->Idfix->GetIconHTML($aTableConfig);
    //$this->IdfixDebug->Profiler(__method__, 'stop');
  }


  public function Events3IdfixActionListBreadcrumb(&$aData) {
    //$this->IdfixDebug->Profiler(__method__, 'start');
    $aData['aBreadcrumb'] = array( //
      'Home' => $this->Idfix->GetUrl($this->Idfix->cConfigName), //
      'Level 1' => $this->Idfix->GetUrl($this->Idfix->cConfigName), //
      'Level 2' => $this->Idfix->GetUrl($this->Idfix->cConfigName), //
      'Level 3' => $this->Idfix->GetUrl($this->Idfix->cConfigName), //
      'Level 4' => $this->Idfix->GetUrl($this->Idfix->cConfigName), //
      );
    //$this->IdfixDebug->Profiler(__method__, 'stop');
  }

  public function Events3IdfixActionListMain(&$aData) {
    //$this->IdfixDebug->Profiler(__method__, 'start');
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
    // Now it is time to check if we need to setup counters
    // for children. We do this after the dataset creation
    // because we need the MainID of every row as a parentID
    $this->PreloadChildCounters($aColumns, $aDataSet);
    // Than postprocess this dataset to provide the
    // exact HTML for display purposes
    $aDisplayDataset = $this->GetDisplayDataset($aDataSet, $aColumns, $aTableConfig);
    // Now build the template variables
    $aData['aHead'] = $aHeader;
    $aData['aBody'] = $aDisplayDataset;
    //$this->IdfixDebug->Profiler(__method__, 'stop');
  }


  public function Events3IdfixActionListPager(&$aPager) {
    //$this->IdfixDebug->Profiler(__method__, 'start');
    // What is the number of records on the page
    $aConfig = $this->Idfix->aConfig;
    $cTable = $this->Idfix->cTableName;
    $aTableConfig = $aConfig['tables'][$cTable];
    $iRecordsByPage = (integer)$aTableConfig['pager'];
    // What is the total number of records???
    $iRecordsTotal = $this->IdfixStorage->CountRecords($aTableConfig['id'], $this->Idfix->iParent);

    // What is the total number of pages
    $iPages = 1;
    if ($iRecordsTotal > $iRecordsByPage and $iRecordsTotal and $iRecordsByPage) {
      $iPages = ceil($iRecordsTotal / $iRecordsByPage);
    }

    // What is the current page? Defaults to 1
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
    //$this->IdfixDebug->Profiler(__method__, 'stop');

  }

  /* Private section for rendering the main list */
  private function GetColumns($aTableConfig) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $cConfigName = $this->Idfix->cConfigName;
    $cTblName = $this->Idfix->cTableName;

    // initialize Output array
    $aColumns = array();
    // Callback processing
    $aList = $aTableConfig['list'];

    if (is_array($aList)) {
      foreach ($aList as $cFieldName => $cColumnName) {
        // Default fieldname and Columnname
        if (is_numeric($cFieldName)) {
          $cFieldName = $cColumnName;
          if (isset($aTableConfig['fields'][$cFieldName]['title'])) {
            $cColumnName = $aTableConfig['fields'][$cFieldName]['title'];
          }
        }

        // If this field is not in the fileslist, skip it.....
        if (!isset($aTableConfig['fields'][$cFieldName])) {
          continue;
        }

        // Just a shortcut
        $aFieldConfig = $aTableConfig['fields'][$cFieldName];

        // Delete access
        if ($aFieldConfig['_name'] == '_delete' and !$this->Idfix->Access($cTblName . '_d')) {
          continue;
        }
        // Edit access
        if ($aFieldConfig['_name'] == '_edit' and !$this->Idfix->Access($cTblName . '_e')) {
          continue;
        }
        // Copy access
        if ($aFieldConfig['_name'] == '_copy' and !$this->Idfix->Access($cTblName . '_c')) {
          continue;
        }

        // Check if we configured field level permissions
        if (isset($aFieldConfig['permissions']) and $aFieldConfig['permissions']) {
          // And check it accordingly
          $cPermission = $aTableConfig['_name'] . '_' . $cFieldName . '_v';
          if (!$this->Idfix->Access($cPermission)) {
            continue;
          }
        }

        // Check if we have a title
        if (!$cColumnName) {
          $cColumnName = $cFieldName;
        }
        // Now store the values
        $aColumns[$cFieldName] = $cColumnName;
      }
    }
    $this->Idfix->Event('ListColumns', $aColumns);


    $this->IdfixDebug->Profiler(__method__, 'stop');
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
  private function GetColumnsHeader($aColumns) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    //$aHeader = $aColumns;
    $this->Idfix->Event('ListHeader', $aColumns);
    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $aColumns;
  }

  /**
   * Return the full datset from the idfix table.
   * - Construct the right parameters for the
   * IdfixStorage->LoadAllRecords() API function
   * 
   * @return
   */
  private function GetRawDataset() {
    $this->IdfixDebug->Profiler(__method__, 'start');
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
    $cOrder = '';
    /* Build the default where clauses*/
    $aWhere = array();
    /* Build the limit clause as an array for convenience
    of the event handler, remember limit is 0-based*/
    $iPage = ($this->Idfix->iObject ? $this->Idfix->iObject : 1);
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

    $this->IdfixDebug->Profiler(__method__ . '::LoadAllRecords', 'start');
    $aRawDataSet = $this->IdfixStorage->LoadAllRecords($iTypeID, $iParentID, $cOrder, $aWhere, $cLimit);
    $this->IdfixDebug->Profiler(__method__ . '::LoadAllRecords', 'stop');


    $this->IdfixDebug->Profiler(__method__, 'stop');
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
  private function GetDisplayDataSet($aRawSet, $aColumns, $aTableConfig) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $aDisplaySet = array();

    foreach ($aRawSet as $aRawRow) {
      $aDisplayRow = array();
      foreach ($aColumns as $cFieldName => $cColumnName) {
        // What is the value we need displayed
        $xFieldData = $aRawRow['MainID'];
        if (isset($aRawRow[$cFieldName])) {
          $xFieldData = $aRawRow[$cFieldName];
        }

        // And the matching field configuration?
        $aFieldConfig = $aTableConfig['fields'][$cFieldName];
        // Postprocess the configuration
        $aFieldConfig = $this->Idfix->PostprocesConfig($aFieldConfig, $aRawRow);
        // Now it's the time to get the visual
        $aDisplayRow[] = $this->GetDisplayDataCell($xFieldData, $aFieldConfig, $aRawRow['MainID']);
      }
      $aDisplaySet[] = $aDisplayRow;
    }
    //print_r($aDisplaySet);
    $this->IdfixDebug->Profiler(__method__, 'stop');
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
  private function GetDisplayDataCell($xFieldData, $aFieldConfig, $iMainID) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $aFieldConfig['__RawValue'] = $xFieldData;

    // Check if we need to set up AJAX edit calls
    if (isset($aFieldConfig['ajax']) and $aFieldConfig['ajax']) {
      $aFieldConfig['__MainID'] = $iMainID;
      $this->Idfix->Event('AjaxDisplayField', $aFieldConfig);
    }
    else {
      $this->Idfix->Event('DisplayField', $aFieldConfig);
    }

    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $aFieldConfig['__DisplayValue'];
  }

  /**
   * If we need to display virtual columns that link to child lists
   * it might be that we need to show the number of children in a badge
   * on the button. 
   * 
   * In that case we use this method to preload all the counters
   * with a single SQL call
   * 
   * First we check if there are vrtual columns in the columnlist
   * with the property counter set to 1
   * 
   * If there are we remember there TypeID's because there can be multiple
   * columns
   * 
   * If any columns are setup for childcounting we also create a list
   * of all the MainID's we are showing in this list. Because we only
   * need a counter for these ID's.
   *'
   * 
   * @param mixed $aColumns
   * @param mixed $aDataSet
   * @return void
   */
  public function PreloadChildCounters($aColumns, $aDataSet) {
    // Do we have a dataset???
    // If not, no need for preloading!!
    if (count($aDataSet) <= 0) {
      return;
    }

    $cTableName = $this->Idfix->cTableName;
    // List of all the Type ID's we need to count
    $aTypeIdList = array();
    // Check all columns
    foreach ($aColumns as $cFieldName => $cColumnHeader) {
      // Get the tableconfig
      $aFieldConfig = $this->Idfix->aConfig['tables'][$cTableName]['fields'][$cFieldName];
      // Ok, the correct type
      if ($aFieldConfig['type'] == 'virtual') {
        // Now check if we need a counter
        if (isset($aFieldConfig['counter']) and $aFieldConfig['counter']) {
          // What is the tablename of the child????
          $cChildTableName = $aFieldConfig['_name'];
          // But stop, it is prepended with an underscore!!! Strip it
          $cChildTableName = substr($cChildTableName, 1);
          // Now get the typeID
          $iTypeID = $this->Idfix->aConfig['tables'][$cChildTableName]['id'];
          // And store it in our little list
          $aTypeIdList[$iTypeID] = $iTypeID;
        }
      }
    }

    // By now we should have a good list of TypeID's we need a counter for.
    // If not, stop processing!!!
    if (count($aTypeIdList) <= 0) {
      return;
    }

    // Create two strings for the preloader
    // 1. TypeID's we need to count
    // 2. parentID's we need to do the counting for.
    $cTypeIdList = implode(',', $aTypeIdList);
    $cParentIdList = implode(',', array_keys($aDataSet));

    //$this->IdfixDebug->Debug('List of TypeIDs for preloading', $cTypeIdList);
    //$this->IdfixDebug->Debug('List of ParentIDs for preloading', $cParentIdList);

    $this->IdfixStorage->PreloadChildCounts($cTypeIdList, $cParentIdList);

  }

}
