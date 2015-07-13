<?php

/**
 * The date view works completely with UNIX timestamps only
 * 
 * Everything we need for a calender view of our data
 * 
 * Looks at the date section of the tableconfig:
 * 
 * #tables
 *   -view=list|date|map
 *   #example
 *      -title=example for dates
 *      #date
 *         -view=week|month  defaults to month
 *         -field=<fieldname>  defaults to TSCreate if empty or not correct field type
 *         -display=<literal with variables> Defaults to table->trail or %Name%
 *      #list
 *      #fields
 *
 * @todo
 * 
 *  
 */

class IdfixListdate extends Events3Module {

  // What kind of view do we need?
  private $cView = 'month'; // or 'week'

  // Whats the first day we need to show? UNIX timestamp
  private $iFirstDayOfView = '';

  // Permission PrePorcessing
  private $bViewDeleteButton = false;
  private $bViewCopyButton = false;
  private $bViewEditButton = false;

  // Fill this with empty values for the fields we cannot see
  private $aViewBlackList = array();
  // And an array with allowed fields to see
  private $aViewWhiteList = array();

  public function Events3IdfixActionListdate(&$output) {
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

    // Set the view mode to week or month
    $this->SetViewMode();

    // Setup the first day of the view
    $this->SetFirstDay();

    // Proprocess all permissions
    $this->SetupPermissions();

    $aTemplateVars = array();

    // Get the title, It's implemented in the main list
    $cHook = 'ActionListTitle';
    $aData = array();
    $this->Idfix->Event($cHook, $aData);
    $aTemplateVars[$cHook] = $this->Idfix->RenderTemplate($cHook, $aData);
    // Get the Breadcrumb trail
    $aTemplateVars['ActionListBreadcrumb'] = $this->Idfix->BreadCrumbs($this->Idfix->iParent);
    // Get the grid, custom for dates
    $cHook = 'ActionListdateMain';
    $aData = array();
    $this->Idfix->Event($cHook, $aData);
    $aTemplateVars['ActionListMain'] = $this->Idfix->RenderTemplate($cHook, $aData);
    // Get the pager
    $cHook = 'ActionListdatePager';
    $aData = array();
    $this->Idfix->Event($cHook, $aData);
    $aTemplateVars['ActionListPager'] = $this->Idfix->RenderTemplate($cHook, $aData);

    // Put them in the template, It's the same as the normal list
    $output .= $this->Idfix->RenderTemplate('ActionList', $aTemplateVars);
    //$this->IdfixDebug->Profiler(__method__, 'stop');
  }


  public function Events3IdfixActionListdateMain(&$aData) {
    //$this->IdfixDebug->Profiler(__method__, 'start');
    // let's grab the configuration settings and get only the part
    // we need now, the specifics for this table
    $aConfig = $this->Idfix->aConfig;
    $cTable = $this->Idfix->cTableName;
    $aTableConfig = $aConfig['tables'][$cTable];

    $aDataSet = $this->GetRawDataset();

    // Now it is time to check if we need to setup counters
    // for children. We do this after the dataset creation
    // because we need the MainID of every row as a parentID
    $this->IdfixList->PreloadChildCounters($this->aViewWhiteList, $aDataSet);

    $aCalender = $this->GetEmptyCalender();

    $aData['calendar'] = $this->FillCalender($aCalender, $aDataSet);

    //$this->IdfixDebug->Profiler(__method__, 'stop');
  }


  public function Events3IdfixActionListdatePager(&$aPager) {
    //$this->IdfixDebug->Profiler(__method__, 'start');
    // What is the number of records on the page
    $aConfig = $this->Idfix->aConfig;
    $cTable = $this->Idfix->cTableName;
    $aTableConfig = $aConfig['tables'][$cTable];
    // What is the total number of records???
    $iRecordsTotal = $this->IdfixStorage->CountRecords($aTableConfig['id'], $this->Idfix->iParent);

    if ($this->cView == 'week') {
      $cNewView = 'month';
      $iPrevious = strtotime('last monday', $this->iFirstDayOfView);
      $iNext = strtotime('next monday', $this->iFirstDayOfView);
      $aPager['cInfo'] = 'Week ' .  date('W o', $this->iFirstDayOfView);

    }
    else {
      $cNewView = 'week';
      $iPrevious = strtotime('first day of last month', $this->iFirstDayOfView);
      $iNext = strtotime('first day of next month', $this->iFirstDayOfView);
      $aPager['cInfo'] = date('F o', $this->iFirstDayOfView);      
    }

    // Put the information in the pager-array for rendering
    $aPager['iRecordsTotal'] = $iRecordsTotal;
    $aPager['cUrlBackward'] = $this->GetUrl($iPrevious);
    $aPager['cUrlForward'] = $this->GetUrl($iNext);
    $aPager['cUrlView'] = $this->GetUrl($this->iFirstDayOfView, $cNewView);


    //$this->IdfixDebug->Debug(__method__, $aPager);
    //$this->IdfixDebug->Profiler(__method__, 'stop');

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
    $cFieldName = $aTableConfig['date']['field'];
    $aWhere = array();
    $iStartTimeStamp = $this->iFirstDayOfView;
    $iStopTimeStamp = strtotime('first day of next month', $iStartTimeStamp);
    if ($this->cView == 'week') {
      $iStopTimeStamp = $iStartTimeStamp + (7 * 24 * 60 * 60);
    }
    $aWhere[] = $cFieldName . ' >= ' . $iStartTimeStamp;
    $aWhere[] = $cFieldName . ' < ' . $iStopTimeStamp;

    $this->IdfixDebug->Debug(__METHOD__, date('Y-m-d H:i:s', $iStartTimeStamp) . ' until ' . date('Y-m-d H:i:s', $iStopTimeStamp));


    // Create a PACKAGE for the eventhandler
    $aPack = array(
      'type' => $iTypeID,
      'parent' => $iParentID,
      'order' => $cOrder,
      'where' => $aWhere,
      'limit' => array(),
      );
    $this->Idfix->Event('ListDataSet', $aPack);

    /* Now get all the values back in variables */
    $iTypeID = $aPack['type'];
    $iParentID = $aPack['parent'];
    $cOrder = $aPack['order'];
    $aWhere = $aPack['where'];

    $this->IdfixDebug->Profiler(__method__ . '::LoadAllRecords', 'start');
    $aRawDataSet = $this->IdfixStorage->LoadAllRecords($iTypeID, $iParentID, $cOrder, $aWhere);
    $this->IdfixDebug->Profiler(__method__ . '::LoadAllRecords', 'stop');


    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $aRawDataSet;
  }


  /**
   * New Date specific functionality
   */

  /**
   * Do we need a month or a weekview?
   * 
   * @return void
   */
  private function SetViewMode() {
    $aCommand = explode('/', parse_url(urldecode($_SERVER['PATH_INFO']), PHP_URL_PATH));
    if (isset($aCommand[7]) and $aCommand[7]) {
      $cView = $aCommand[7];
      if (stripos(';week;month;', $cView)) {
        $this->cView = $cView;
      }
    }
  }

  /**
   * Analyse the url and see if we have a view mode. Default is month.
   * 
   * @param mixed $cFirstDay
   * @return void
   */
  private function GetUrl($iFirstDay, $cView = '') {
    if (!$cView) {
      $cView = $this->cView;
    }
    return $this->Idfix->GetUrl('', '', '', $iFirstDay, null, 'listdate/' . $cView);
  }

  /**
   * Read the requested date from the url, parse it and check what should
   * be the first date of the view
   * 
   * @return void
   */
  private function SetFirstDay() {
    if ($this->Idfix->iObject > 1) {
      $iTime = $this->Idfix->iObject;
    }
    else {
      $iTime = time();
    }

    // Count back to midnight ...
    $iTime = strtotime('midnight', $iTime);

    if ($this->cView == 'week') {
      $bIsMonday = (boolean)(date('N', $iTime) == 1);
      $this->iFirstDayOfView = $iTime;
      if (!$bIsMonday) {
        $this->iFirstDayOfView = strtotime('last monday', $iTime);
      }
    }
    else {
      $this->iFirstDayOfView = strtotime('first day of', $iTime);
    }

    //$this->IdfixDebug->Debug(__METHOD__, get_defined_vars());
    //$this->IdfixDebug->Debug(__METHOD__, date('Y-m-d H:i:s', $this->iFirstDayOfView) );
    
  }

  /**
   * Event handler for the table configuration setup
   * 
   * @return void
   */
  public function Events3IdfixTableConfig(&$aConfig) {
    if (!isset($aConfig['date']) or !is_array($aConfig['date'])) {
      $aConfig['date'] = array();
    }
    if (!isset($aConfig['date']['view'])) {
      $aConfig['date']['view'] = 'month';
    }
    if (!isset($aConfig['date']['display'])) {
      $aConfig['date']['display'] = '%Name%';
      // Default to trail
      if (isset($aConfig['trail'])) {
        $aConfig['date']['display'] = $aConfig['trail'];
      }
    }
    // Check the date field for correct type
    $cDateField = 'TSCreate';
    if (isset($aConfig['date']['field'])) {
      $cDateField = $aConfig['date']['field'];
    }
    $bCorrectType = (boolean)(isset($aConfig['fields'][$cDateField]['type']) and strtolower($aConfig['fields'][$cDateField]['type']) == 'date');
    $bSystemField = (boolean)($cDateField == 'TSCreate' or $cDateField == 'TSChange');
    $aColumns = $this->IdfixStorage->GetTableSpaceColumns();
    $bIsSqlField = (boolean)(isset($aColumns[$cDateField]));
    if (!($bCorrectType and $bIsSqlField) or !$bSystemField) {
      $cDatefield = 'TSCreate';
    }
    $aConfig['date']['field'] = $cDateField;

  }

  /**
   * Get empty week or month calender
   * 
   * @return
   */
  private function GetEmptyCalender() {
    // Week is simple :-)
    if ($this->cView == 'week') {
      return array($this->GetEmptyWeek($this->iFirstDayOfView));
    }

    $aReturn = array();

    $iStartMonth = $this->iFirstDayOfView; // Timestamp first day of the month
    $iStopMonth = strtotime('last day of', $iStartMonth); // timestamp last day of the month
    // Begin of the grid
    $iStartGrid = strtotime('last monday', $iStartMonth);

    // Create the weeks
    $iStartWeek = $iStartGrid;
    while ($iStartWeek <= $iStopMonth) {
      $aReturn[] = $this->GetEmptyWeek($iStartWeek, date('n', $iStartMonth));
      $iStartWeek += (60 * 60 * 24 * 7); // Calculate next week
    }

    return $aReturn;
  }

  /**
   * Create empty week array
   * 
   * @param mixed $iStartTimeStamp
   * @return array week
   */
  private function GetEmptyWeek($iStartTimeStamp, $iBoundMonth = 0) {
    $aReturn = array();
    for ($i = 0; $i < 7; $i++) {
      // Check boundaries
      $bEnabled = true;
      if ($iBoundMonth) {
        $bEnabled = (boolean)(date('n', $iStartTimeStamp) == $iBoundMonth);
      }

      $aReturn[$i] = array(
        'time' => $iStartTimeStamp,
        'enabled' => $bEnabled,
        'data' => '&nbsp;',
        );
      $iStartTimeStamp += (60 * 60 * 24);
    }
    return $aReturn;
  }

  /**
   * Process the dataset and store the individual items in the correct
   * slots on the calendar.
   * 
   * @param array $aCalender
   * @param array $aDataSet
   * @return
   */
  private function FillCalender($aCalender, $aDataSet) {
    $aTableConfig = $this->Idfix->aConfig['tables'][$this->Idfix->cTableName];
    // We know, that this is a correct fieldname because we checked it in the
    // ecvent that set the defaults
    $cDateField = $aTableConfig['date']['field'];

    // Create the dataset that's sorted by date for easy setting the calender items
    $aDataSetByDate = array();

    foreach ($aDataSet as $aRecord) {
      $iRecordTime = $aRecord[$cDateField];
      // First get an item to display
      $cHtmlItem = $this->GetDisplayItem($aRecord, $aTableConfig, $iRecordTime);
      // Second, Store it in the sorted dataset
      $aDataSetByDate[date('Ymd', $iRecordTime)][] = $cHtmlItem;
    }

    // Now go through the calender and set the data
    foreach ($aCalender as &$aWeek) {
      foreach ($aWeek as &$aDay) {
        $cDate = date('Ymd', $aDay['time']);
        if (isset($aDataSetByDate[$cDate])) {
          $aDay['data'] = implode($aDataSetByDate[$cDate]);
        }
      }
    }

    //$this->IdfixDebug->Debug('FilleCalendar', get_defined_vars());
    return $aCalender;
  }

  private function GetDisplayItem($aRecord, $aTableConfig, $iRecordTime) {
    // Check access rights and remove non-viewable items from the record
    $aRecord = array_merge($aRecord, $this->aViewBlackList);
    // And postprocess it for the display item
    $aTableConfig = $this->Idfix->PostprocesConfig($aTableConfig, $aRecord);

    // What do we need to show??
    $cDisplayData = $aTableConfig['date']['display'];

    // Edit button
    $cEditButton = '';
    if ($this->bViewEditButton) {
      $aFieldConfig = $aTableConfig['fields']['_edit'];
      $aFieldConfig['__RawValue'] = $aRecord['MainID'];
      $this->Idfix->Event('DisplayField', $aFieldConfig);
      $cEditButton = $aFieldConfig['__DisplayValue'];
    }

    // Delete button
    $cDeleteButton = '';
    if ($this->bViewDeleteButton) {
      $aFieldConfig = $aTableConfig['fields']['_delete'];
      $aFieldConfig['__RawValue'] = $aRecord['MainID'];
      $this->Idfix->Event('DisplayField', $aFieldConfig);
      $cDeleteButton = $aFieldConfig['__DisplayValue'];
    }

    // Copy button
    $cCopyButton = '';
    if ($this->bViewCopyButton) {
      $aFieldConfig = $aTableConfig['fields']['_copy'];
      $aFieldConfig['__RawValue'] = $aRecord['MainID'];
      $this->Idfix->Event('DisplayField', $aFieldConfig);
      $cCopyButton = $aFieldConfig['__DisplayValue'];
    }

    // Full data list
    $aList = array();
    foreach ($aTableConfig['fields'] as $cFieldName => $aFieldConfig) {
      // Dont't show this field at all
      if (isset($this->aViewBlackList[$cFieldName])) {
        continue;
      }
      // Button check, don't render these buttons
      if (stripos(';_delete;_copy;_edit;', $cFieldName)) {
        continue;
      }

      // Childs access checking ...
      if ($aFieldConfig['type'] == 'virtual') {
        $cPermission = $aFieldConfig['_tablename'] . '_v';
        if (!$this->Idfix->Access($cPermission)) {
          continue;
        }
      }

      $aFieldConfig['__RawValue'] = (isset($aRecord[$cFieldName]) ? $aRecord[$cFieldName] : $aRecord['MainID']);
      $this->Idfix->Event('DisplayField', $aFieldConfig);
      $cTitle = $aFieldConfig['title'];
      $aList[$cTitle] = $aFieldConfig['__DisplayValue'];
    }

    // And return the fully rendered item
    $aTemplateVars = array(
      'display' => $cDisplayData,
      'edit' => $cEditButton,
      'copy' => $cCopyButton,
      'delete' => $cDeleteButton,
      'title' => $aTableConfig['title'],
      'icon' => $this->Idfix->GetIconHTML($aTableConfig),
      'fulldate' => date('l F jS o', $iRecordTime),
      'data' => $aList,
      'uid' => 'date-item-' . $aRecord['MainID'],
      );

    return $this->Idfix->RenderTemplate('ActionListdateMainItem', $aTemplateVars);
  }


  /**
   * Do all the preprocessing for permissions here.
   * Just to save time and don't clutter up the code
   * 
   * The blacklist is also very easy to use, we can now merge
   * a record with the blacklist to override any non viewable items
   * with an empty value.
   * 
   * That way we can do the postprocessing of the config without worrying
   * about seeing non-accesible items
   * 
   * @return void
   */
  private function SetupPermissions() {
    $cTableName = $this->Idfix->cTableName;
    $aTableConfig = $this->Idfix->aConfig['tables'][$cTableName];
    $aDateConfig = $aTableConfig['date'];
    $aFieldList = array_keys($aTableConfig['fields']);

    // Table level permissions EDIT
    $this->bViewEditButton = $this->Idfix->Access($cTableName . '_e');

    // Table level permissions COPY
    $this->bViewCopyButton = $this->Idfix->Access($cTableName . '_a');

    // Table level permissions DELETE
    $this->bViewDeleteButton = $this->Idfix->Access($cTableName . '_d');

    // Field level permissions
    foreach ($aTableConfig['fields'] as $cFieldName => $aFieldConfig) {
      $bAllowView = true;
      $bCheckPermissions = (isset($aFieldConfig['permissions']) and $aFieldConfig['permissions']);
      if ($bCheckPermissions) {
        $bAllowView = $this->Idfix->Access($cTableName . '_' . $cFieldName . '_v');
      }
      // Set a blacklist item
      if (!$bAllowView) {
        $this->aViewBlackList[$cFieldName] = '';
      } else{
         $this->aViewWhiteList[$cFieldName] = '';
      }
    }


  }

}
