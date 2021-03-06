<?php

class IdfixDefault extends Events3Module {

  private $oIdfix;

  /**
   * 
   * Set default values for the idfix configuration. This way we can do
   * a quick prototype by just defining only tables and fields in the system.
   * For fields we don't need to specifiy anything else than a name
   * with an empty list.
   *
   * @param
   *   mixed $aConfig
   * @return
   *   void
   */
  public function Events3IdfixGetConfigAfter() {
    $this->IdfixDebug->Profiler(__method__ . 'doorlooptest', 'start');
    // Load modules we need
    $this->oIdfix = $this->load('Idfix');
    // Get Options from the idfix module
    $aConfig = &$this->oIdfix->aConfig;
    $cConfigName = $this->oIdfix->cConfigName;

    $this->SetDefaultValue($aConfig, 'title', 'Untitled configuration');
    $this->SetDefaultValue($aConfig, 'description', 'Description for this untitled configuration');
    $this->SetDefaultValue($aConfig, 'icon', 'book');
    $this->SetDefaultValue($aConfig, 'iconsize', 16);
    $this->SetDefaultValue($aConfig, '_name', $cConfigName);
    $this->SetDefaultValue($aConfig, 'iconlib', "bootstrap");

    // Create a default tablespace
    $cTableSpace = $this->oIdfix->ValidIdentifier('idfix_' . trim($cConfigName));
    $this->SetDefaultValue($aConfig, 'tablespace', $cTableSpace);
    // Create a default filespace
    $cFileSpace = $this->ev3->PublicPath . '/' . $cTableSpace;
    $this->SetDefaultValue($aConfig, 'filespace', $cFileSpace);

    if (isset($aConfig['tables'])) {
      $id_counter = 500;
      foreach ($aConfig['tables'] as $cTableName => &$aTableConfig) {
        $id_counter += 10;
        $aTableConfig['_name'] = $cTableName;
        $this->AlterTable($cConfigName, $cTableName, $aTableConfig, $aConfig, $id_counter);
        $this->AlterSearch($cConfigName, $cTableName, $aTableConfig, $aConfig);
        $this->AlterSort($cConfigName, $cTableName, $aTableConfig, $aConfig);

        // Call an event for changing the tableconfig
        $this->Idfix->Event('TableConfig', $aTableConfig);
      }
    }
    else {
      $aConfig['tables'] = array();
    }
    $this->IdfixDebug->Profiler(__method__ . 'doorlooptest', 'stop');
  }

  /**
   * $this->AlterSort()
   *
   * @param
   *   mixed $cConfigName
   * @param
   *   mixed $cTableName
   * @param
   *   mixed $aTableConfig
   * @param
   *   mixed $aConfig
   */
  private function AlterSort($cConfigName, $cTableName, &$aTableConfig, $aConfig) {
    $aFieldList = $this->IdfixStorage->GetIdfixColumns();
    // If there's a sort array, ok, otherwise build a default on all
    // the available system fields
    if (isset($aTableConfig['sort']) and is_array($aTableConfig['sort'])) {

      // Are these values MySql columns??
      foreach ($aTableConfig['sort'] as $xSortID => $xSortValue) {
        // We do not know which part is the fieldname
        $cFieldName = $xSortID;
        if (is_numeric($cFieldName)) {
          $cFieldName = $xSortValue;
        }
        // Now test the name
        if (!isset($aFieldList[$cFieldName])) {
          // NO Real sql column!!!
          unset($aTableConfig['sort'][$xSortID]);
        }
      }
      return;
    }

    $aSort = array();
    foreach ($aFieldList as $cFieldName => $aFieldConfig) {
      // Is this columns used?
      if (isset($aTableConfig['fields'][$cFieldName])) {
        $aSort[$cFieldName] = '';
        // Special case!!
        if ($cFieldName == 'Weight') {
          $aSort[$cFieldName] = 'asc';
        }
      }
    }

    $aTableConfig['sort'] = $aSort;

  }

  /**
   * $this->AlterSearch()
   *
   * Postprocess the search array if present and set the field config as default
   *
   * @param
   *   mixed $cTableName
   * @param
   *   mixed $aConfig
   * @param
   *   mixed $aFullConfig
   * @return
   *   void
   */
  private function AlterSearch($cConfigName, $cTableName, &$aConfig, $aFullConfig) {

    // Set default to an empty array
    if (!array_key_exists('search', $aConfig)) {
      $aConfig['search'] = array();
    }
    if (!is_array($aConfig['search'])) {
      $aConfig['search'] = array();
    }

    // Preprocess all the searchfields for numeric indexes
    // In that case the search element is defined as a simple list
    // We need to correct that
    $new_list = array();
    foreach ($aConfig['search'] as $index => $cFieldName) {
      if (is_numeric($index) or !is_array($cFieldName)) {
        $cFieldName = (string )$cFieldName;
        $new_list[$cFieldName] = array();
      }
      else {
        $new_list[$index] = $cFieldName;
      }
    }
    $aConfig['search'] = $new_list;

    // Now process all the searchfields
    foreach ($aConfig['search'] as $cFieldName => &$aSearchConfig) {
      // Get the defaults from the field
      $aFieldConfig = array();
      if (isset($aConfig['fields'][$cFieldName]) and is_array($aConfig['fields'][$cFieldName])) {
        $aFieldConfig = $aConfig['fields'][$cFieldName];
      }
      // .. and merge them with the existing search fields
      $aSearchConfig = array_merge($aFieldConfig, $aSearchConfig);

      // Build a default WHERE template but check if we have a real or
      // virtual column
      // This way we can implement basic searching and avoiding most
      // SQl errors
      $aFields = $this->IdfixStorage->GetIdfixColumns();
      if (isset($aFields[$cFieldName])) {
        $this->SetDefaultValue($aSearchConfig, 'sql', "{$cFieldName} = '%value'");
      }
      else {
        $this->SetDefaultValue($aSearchConfig, 'sql', "data LIKE '%%value%'");
      }


    }
    //print_r($aConfig['search']);
  }

  /**
   * $this->AlterTable()
   *
   * Check the table configuration.
   *
   * System fields are preceded with an underscore, so don't use
   * underscores for your own fields.
   * Maybe we could check upon that later.
   *
   * @param
   *   mixed $cTableName
   * @param
   *   mixed $aConfig
   * @param
   *   mixed $aFullConfig
   * @return
   *   void
   */
  private function AlterTable($cConfigName, $cTableName, &$aConfig, $aFullConfig, $id_counter) {

    // Standard properties
    $this->SetDefaultValue($aConfig, 'title', $cTableName);
    $this->SetDefaultValue($aConfig, 'description', '');
    $this->SetDefaultValue($aConfig, 'icon', 'list');
    $this->SetDefaultValue($aConfig, 'pager', 20);


    // Default ID
    //static $id_counter = 500;
    //$id_counter += 10;
    if (!array_key_exists('id', $aConfig)) {
      $this->SetDefaultValue($aConfig, 'id', $id_counter);
    }

    if (isset($aConfig['groups'])) {
      foreach ($aConfig['groups'] as $cGroupName => &$aGroupConfig) {
        $this->AlterGroup($cTableName, $cGroupName, $aGroupConfig);
      }
    }

    // Set default values for all fields
    if (isset($aConfig['fields'])) {

      $aFieldConfig = &$aConfig['fields'];

      // Edit button
      $aButton = array(
        "type" => "virtual",
        "title" => "Edit",
        "icon" => "edit",
        "href" => $this->oIdfix->GetUrl('%_config%', $cTableName, '', '%MainID%', '%ParentID%', 'edit'),
        "class" => "btn btn-xs btn-default",
        "_tablename" => $cTableName,
        "_name" => '_edit',
        );
      $this->SetDefaultValue($aFieldConfig, '_edit', $aButton);


      // Copy button
      $aButton = array(
        "type" => "virtual",
        "title" => "Copy",
        "icon" => "camera",
        "href" => $this->oIdfix->GetUrl('%_config%', $cTableName, '', '%MainID%', '%ParentID%', 'copy'),
        "class" => "btn btn-xs btn-default",
        "_tablename" => $cTableName,
        "_name" => '_copy',
        );
      $this->SetDefaultValue($aFieldConfig, '_copy', $aButton);


      // Set a default edit button for all of the childs
      if (isset($aConfig['childs']) and is_array($aConfig['childs'])) {
        foreach ($aConfig['childs'] as $cChildName) {
          $aChildConfig = $aFullConfig['tables'][$cChildName];
          $aButton = array(
            "type" => "virtual",
            "title" => "{$aChildConfig['title']}",
            "value" => "{$aChildConfig['title']}",
            "icon" => isset($aChildConfig['icon']) ? $aChildConfig['icon'] : 'zoom-in',
            "counter" => 1, // Show childcounter
            "href" => $this->oIdfix->GetUrl('%_config%', $cChildName, '', 1, '%MainID%', 'list'),
            "class" => "btn btn-xs btn-primary",
            "destination" => false,
            "_tablename" => $cTableName,
            "_name" => '_' . $cChildName,
            );
          $this->SetDefaultValue($aFieldConfig, '_' . $cChildName, $aButton);
        }
      }

      // Delete button
      $aButton = array(
        "type" => "virtual",
        "confirm" => "Are you sure you want to delete this {$cTableName}?",
        "title" => "Delete",
        "icon" => "remove",
        "href" => $this->oIdfix->GetUrl('%_config%', $cTableName, '', '%MainID%', '%ParentID%', 'delete'),
        "class" => "btn btn-xs btn-danger",
        "_tablename" => $cTableName,
        "_name" => '_delete',
        );
      $this->SetDefaultValue($aFieldConfig, '_delete', $aButton);


      // What fields to display in the list if not defined
      if (!isset($aConfig['list'])) {
        $aList = array_keys($aConfig['fields']);
        // Edit and copy up front
        unset($aList['_copy']);
        unset($aList['_edit']);
        array_unshift($aList, '_copy');
        array_unshift($aList, '_edit');

        $this->SetDefaultValue($aConfig, 'list', $aList);
      }
      /**
       * BUGFIX 2014-10-27
       * Move these default settings to the bottom
       * Now the virtual fields have presedence over these properties
       * allowing us to set other defaults in the config
       */
      foreach ($aConfig['fields'] as $cFieldName => &$aFieldConfig) {
        $aFieldConfig['_tablename'] = $cTableName;
        $aFieldConfig['_name'] = $cFieldName;
        $this->AlterField($cTableName, $cFieldName, $aFieldConfig, $aFullConfig);
      }

    }
  }

  /**
   * $this->AlterField()
   *
   * @param
   *   mixed $cTableName
   * @param
   *   mixed $cFieldName
   * @param
   *   mixed $aFieldConfig
   * @return
   *   void
   */
  private function AlterField($cTableName, $cFieldName, &$aFieldConfig, $aFullConfig) {
    $this->SetDefaultValue($aFieldConfig, 'type', 'text');
    $this->SetDefaultValue($aFieldConfig, 'title', $cFieldName);
    $this->SetDefaultValue($aFieldConfig, 'description', '');

    if ($aFieldConfig['type'] == 'file') {
      $this->SetDefaultValue($aFieldConfig, 'icon', 'download.png');
      // Maximum size in bytes: 2 MB
      $this->SetDefaultValue($aFieldConfig, 'max', 2048 * 1024);
    }

    if ($aFieldConfig['type'] == 'checkbox') {
      $this->SetDefaultValue($aFieldConfig, 'options', array(0 => 'No', 1 => 'Yes'));
    }

    if ($aFieldConfig['type'] == 'virtual') {
      $this->SetDefaultValue($aFieldConfig, 'class', 'btn btn-xs btn-info');
      $this->SetDefaultValue($aFieldConfig, 'href', '#');
      $this->SetDefaultValue($aFieldConfig, 'value', $aFieldConfig['title']);
      $this->SetDefaultValue($aFieldConfig, 'icon', 'play');
    }
    else {
      $this->SetDefaultValue($aFieldConfig, 'class', 'form-control');
    }

    // Check if this field belongs to a group, and if so set the default column width
    // to the group default, otherwise make it as wide as the container.
    if (isset($aFieldConfig['group'])) {
      $cGroupName = $aFieldConfig['group'];
      if (isset($aFullConfig['tables'][$cTableName]['groups'][$cGroupName])) {

        $aGroupConfig = $aFullConfig['tables'][$cTableName]['groups'][$cGroupName];
        if (isset($aGroupConfig['cols'])) {
          $iCols = (integer)$aGroupConfig['cols'];
          $this->SetDefaultValue($aFieldConfig, 'cols', $iCols);
        }
      }
    }
    $this->SetDefaultValue($aFieldConfig, 'cols', '12');

    // Last but not least, see if this field is in need of any
    // runtime postprocessing
    if (!$this->CheckRecursiveFieldNeedsPostprocessing($aFieldConfig)) {
      $aFieldConfig['_NoPP'] = 1;
      //echo 1;
    }

    // Call an event for changing the fieldconfig
    $this->Idfix->Event('FieldConfig', $aFieldConfig);


  }

  /**
   * Give this method a configuration array and it will check if this
   * configuration needs any postprocessing.
   * 
   * @param mixed $aConfig
   * @return void
   */
  private function CheckRecursiveFieldNeedsPostprocessing($aConfig) {
    $bReturn = false;
    foreach ($aConfig as $cConfigKey => $xConfigValue) {
      $bCheck = false;

      if (is_array($xConfigValue)) {
        $bCheck = $this->CheckRecursiveFieldNeedsPostprocessing($xConfigValue);
      }
      else {
        $bCheck = (strpos($xConfigValue, '%') !== false or strpos($xConfigValue, '@') !== false);
      }

      if ($bCheck) {
        // This structure needs postprocessing
        $bReturn = true;
        // No need for futher checking, exit the loop
        break;
      }
    }

    return $bReturn;
  }

  /**
   * $this->AlterGroup()
   *
   * @param
   *   mixed $cTableName
   * @param
   *   mixed $cGroupName
   * @param
   *   mixed $aGroupConfig
   * @return
   *   void
   */
  private function AlterGroup($cTableName, $cGroupName, &$aGroupConfig) {
    static $iWeight = -100;
    $iWeight++;
    $this->SetDefaultValue($aGroupConfig, 'weight', $iWeight);
    $this->SetDefaultValue($aGroupConfig, 'type', 'fieldset');
    $this->SetDefaultValue($aGroupConfig, 'title', $cGroupName);
    $this->SetDefaultValue($aGroupConfig, 'collapsible', 1);
    $this->SetDefaultValue($aGroupConfig, 'collapsed', 0);
    $this->SetDefaultValue($aGroupConfig, 'description', '');
    $this->SetDefaultValue($aGroupConfig, 'icon', 'pushpin');
  }

  /**
   * $this->SetDefaultValue()
   *
   * Set an optional value in the configuration array.
   * If the value is an array, every element of that array is checked also
   * We could have used recursion here, but that's not so performance friendly
   * and we only need one level at this moment.
   *
   * @param
   *   mixed $aConfig
   * @param
   *   mixed $key
   * @param
   *   mixed $xValue
   * @return
   *   void
   */
  private function SetDefaultValue(&$aConfig, $key, $xValue) {

    // Multiple config values to check
    if (is_array($xValue)) {
      foreach ($xValue as $check_key => $set_value) {
        if (!isset($aConfig[$key][$check_key])) {
          $aConfig[$key][$check_key] = $set_value;
        }
      }
    }
    elseif (!isset($aConfig[$key])) {
      $aConfig[$key] = $xValue;
    }

  }
}
