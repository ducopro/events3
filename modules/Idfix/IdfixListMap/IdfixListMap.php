<?php

/**
 * The map view work with type=address fields only.
 * Everything we need for a Google Maps view of our data
 * Looks at the map section of the tableconfig:
 * 
 * Note: View is not available if there are no address fields defined
 * 
 * #tables
 *   -view=list|date|map
 *   #example
 *      -title=example for a Google Map
 *      #map
 *          -field=<fieldname>  defaults to first address field
 *          -display=<literal with variables> Defaults to table->trail or %Name%
 *      #list
 *      #fields
 *          -type=address
 *          -title=Google Map Address
 *
 * @todo
 * 
 *  
 */

class IdfixListmap extends Events3Module {

  // Permission PreProcessing
  private $bViewDeleteButton = false;
  private $bViewCopyButton = false;
  private $bViewEditButton = false;

  // Fill this with empty values for the fields we cannot see
  private $aViewBlackList = array();
  // And an array with allowed fields to see
  private $aViewWhiteList = array();

  public function Events3IdfixActionListmap(&$output) {
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

    // Preprocess all permissions
    $this->SetupPermissions();

    $aTemplateVars = array();

    // Get the title, It's implemented in the main list
    $cHook = 'ActionListTitle';
    $aData = array();
    $this->Idfix->Event($cHook, $aData);
    $aTemplateVars[$cHook] = $this->Idfix->RenderTemplate($cHook, $aData);

    // Get the Breadcrumb trail
    $aTemplateVars['ActionListBreadcrumb'] = $this->Idfix->BreadCrumbs($this->Idfix->iParent);

    // Get the grid, custom for a Google Map
    $cHook = 'ActionListmapMain';
    $aData = array();
    $this->Idfix->Event($cHook, $aData);
    $aTemplateVars['ActionListMain'] = $this->Idfix->RenderTemplate($cHook, $aData);

    // Get the pager
    //$cHook = 'ActionListmapPager';
    //$aData = array();
    //$this->Idfix->Event($cHook, $aData);
    $aTemplateVars['ActionListPager'] = ''; //$this->Idfix->RenderTemplate($cHook, $aData);

    // Put them in the template, It's the same as the normal list
    $output .= $this->Idfix->RenderTemplate('ActionList', $aTemplateVars);
    //$this->IdfixDebug->Profiler(__method__, 'stop');
  }


  /**
   * IdfixListmap::Events3IdfixActionListmapMain()
   * 
   * @param mixed $aData
   * @return void
   */
  public function Events3IdfixActionListmapMain(&$aData) {
    //$this->IdfixDebug->Profiler(__method__, 'start');
    // let's grab the configuration settings and get only the part
    // we need now, the specifics for this table
    $aConfig = $this->Idfix->aConfig;
    $cTable = $this->Idfix->cTableName;
    $aTableConfig = $aConfig['tables'][$cTable];
    $cMapField = $aTableConfig['map']['field'];
    $aMapConfig = $aTableConfig['map'];

    // The result of the loadrecords method from the storage module
    $aDataSet = $this->GetRawDataset();

    // Now it is time to check if we need to setup counters
    // for children. We do this after the dataset creation
    // because we need the MainID of every row as a parentID
    $this->IdfixList->PreloadChildCounters($this->aViewWhiteList, $aDataSet);

    $aData['js'] = $this->CreateMarkers($aDataSet, $cMapField , $aMapConfig);

  }

  /**
   * Create all the javascript for building a map with markers
   * 
   * @param mixed $aDataSet
   * @param mixed $cFieldName
   * @param mixed $cDisplay
   * @return
   */
  private function CreateMarkers($aDataSet, $cFieldName, $aMapConfig) {
   $cReturn = '';
   foreach($aDataSet as $iMainId => $aRecord) {
      if($aRecord[$cFieldName]) {
         $aPPMapConfig = $this->Idfix->PostprocesConfig($aMapConfig, $aRecord);
         $cTooltip = $aPPMapConfig['display'];
      }
   }
   
   return $cReturn;
  }


  private function GetDisplayItem($aRecord, $aTableConfig) {
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
      'data' => $aList,
      'uid' => 'date-item-' . $aRecord['MainID'],
      );

    return $this->Idfix->RenderTemplate('ActionListmapMainItem', $aTemplateVars);
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
      }
      else {
        $this->aViewWhiteList[$cFieldName] = '';
      }
    }
  }

  /**
   * Event handler for the table configuration setup
   * 
   * @return void
   */
  public function Events3IdfixTableConfig(&$aConfig) {
    if (!isset($aConfig['map']) or !is_array($aConfig['map'])) {
      $aConfig['map'] = array();
    }

    if (!isset($aConfig['map']['display'])) {
      $aConfig['map']['display'] = '%Name%';
      // Default to trail
      if (isset($aConfig['trail'])) {
        $aConfig['map']['display'] = $aConfig['trail'];
      }
    }

    // Get the first, default address field
    $cDefaultAddressField = '';
    foreach ($aConfig['fields'] as $cFieldName => $aFieldConfig) {
      if (isset($aFieldConfig['type']) and strtolower($aFieldConfig['type']) == 'address') {
        $cDefaultAddressField = $cFieldName;
        break;
      }
    }

    // Check the map field for correct type
    $cMapField = $cDefaultAddressField;
    if (isset($aConfig['map']['field'])) {
      $cMapField = $aConfig['map']['field'];
    }
    $bCorrectType = (boolean)(isset($aConfig['fields'][$cMapField]['type']) and strtolower($aConfig['fields'][$cMapField]['type']) == 'address');
    if (!$bCorrectType) {
      $cMapField = $cDefaultAddressField;
    }
    $aConfig['map']['field'] = $cMapField;

  }
  /**
   * Return the full dataset from the idfix table.
   * Construct the right parameters for the
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


}
