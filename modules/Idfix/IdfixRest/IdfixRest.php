<?php

/**
 * Implement REST services
 * 
 */

class IdfixRest extends Events3Module {

  // Which HTTP response codes do we support

  // Any wrong parameter like wrong tablename
  const RESPONSE_BAD_REQUEST = 400;
  // No access according to user-config
  const RESPONSE_UNAUTHORIZED = 401;
  // Object like mainid not found in table
  const RESPONSE_NOT_FOUND = 404;

  /**
   * Main action handler.
   * 
   * URI:
   * <config>/<table>/<REST METHOD>/<MainID>/<ParentID>/rest/<username>/<hash or password>/<... additional parameters>
   * 
   * @param string $output Not used. Instead a JSON package is returned.
   * @return void
   */
  public function Events3IdfixActionRest(&$output, $aParams) {
    //$this->iStart = microtime(true);
    // On the field position we have the REST method
    $cMethod = 'ActionRest' . ucfirst($this->Idfix->cFieldName);
    if (method_exists($this, $cMethod)) {
      $this->$cMethod($aParams);
    }
  }

  /**
   * IdfixRest::ActionRestGet()
   * Get single entity or a collection
   * 
   * @param mixed $aParams
   * @return void
   */
  private function ActionRestGet($aParams) {
    // Set error if table not found
    $aTableConfig = $this->GetTableConfig();
    $iTypeID = $aTableConfig['id'];

    // Check table level access
    if (!$this->Idfix->Access($this->Idfix->cTableName . '_v')) {
      $this->ExitWithError(self::RESPONSE_UNAUTHORIZED);
    }

    // Get a fieldlist of allowed fields for this user and this config
    // also check field level permissions
    $aAllowedFields = $this->AllowedFieldListForGet($aTableConfig);
    //$this->log($aAllowedFields);

    // Two posibillities
    // 1. Only 1 record if we have a mainid
    // 2. a collection of records
    if ($this->Idfix->iObject) {
      // Get all the data
      $aData = $this->IdfixStorage->LoadRecord($this->Idfix->iObject);
      // Record found?
      if (!isset($aData['MainID'])) {
        $this->ExitWithError(self::RESPONSE_NOT_FOUND);
      }
      // Check if the type_id matches
      if( $aData['TypeID'] != $iTypeID  ) {
        $this->ExitWithError(self::RESPONSE_BAD_REQUEST);
      }
      
      // And filter it
      $aData = $this->FilterRecord($aData, $aAllowedFields);
    }
    else {
      // Get all the records
      $aData = $this->IdfixStorage->LoadAllRecords($iTypeID, $this->Idfix->iParent);
      // And filter them for allowed fields
      $aData = $this->Filterrecords($aData, $aAllowedFields);
      // Collection can be empty. No problem
      // No response not found needed
    }


    $this->SendBack($aData);
  }

  /**
   * IdfixRest::ActionRestPost()
   * 
   * ADD... Create a new entity.....
   * Collections are not supported
   * 
   * @param mixed $aParams
   * @return void
   */
  private function ActionRestPost($aParams) {
    // Set error if table not found
    $aTableConfig = $this->GetTableConfig();
    // Check table level access
    if (!$this->Idfix->Access($this->Idfix->cTableName . '_a')) {
      $this->ExitWithError(self::RESPONSE_UNAUTHORIZED);
    }
    // Get the list of fields we are allowed to add
    $aAllowedFields = $this->AllowedFieldListForPost($aTableConfig);
    // Get the list of values to store
    $aValuesToAdd = $_POST;
    // Filter this list by allowed fields
    $aValuesToAddFiltered = $this->FilterRecord($aValuesToAdd, $aAllowedFields);
    // Add typeid according to the tableconfig
    $aValuesToAddFiltered['TypeID'] = $aTableConfig['id'];
    // Add parent-id according to the url
    $aValuesToAddFiltered['ParentID'] = $this->Idfix->iParent;

    // We now have a very clean list to process. Perfect.
    $iNewId = $this->IdfixStorage->SaveRecord($aValuesToAddFiltered);

    // Add ID to the list and set it back for the client
    $aValuesToAddFiltered['MainID'] = $iNewId;
    $this->SendBack($aValuesToAddFiltered);
  }


  /**
   * IdfixRest::ActionRestPut()
   * 
   * Update handler
   * 
   * @param mixed $aParams
   * @return void
   */
  private function ActionRestPut($aParams) {
    // Set error if table not found
    $aTableConfig = $this->GetTableConfig();
    // Check table level access
    if (!$this->Idfix->Access($this->Idfix->cTableName . '_e')) {
      $this->ExitWithError(self::RESPONSE_UNAUTHORIZED);
    }
    // Check if the record we are trying to update realy belongs to this table
    $aOldRecord = $this->IdfixStorage->LoadRecord($this->Idfix->iObject);
    if (!isset($aOldRecord['MainID'])) {
      $this->ExitWithError(self::RESPONSE_NOT_FOUND);
    }
    if ($aOldRecord['TypeID'] != $aTableConfig['id']) {
      $this->ExitWithError(self::RESPONSE_BAD_REQUEST);
    }

    // Get the list of fields we are allowed to change
    $aAllowedFields = $this->AllowedFieldListForPut($aTableConfig);
    // Get the list of values to store
    $aValuesToAdd = $_POST;
    // Filter this list by allowed fields
    $aValuesToAddFiltered = $this->FilterRecord($aValuesToAdd, $aAllowedFields);

    // By now we have a clean list. Make sure we are updating the ciorrect record
    $aValuesToAddFiltered['MainID'] = $this->Idfix->iObject;

    // We now have a very clean list to process. Perfect.
    $this->IdfixStorage->SaveRecord($aValuesToAddFiltered);
    $this->SendBack($aValuesToAddFiltered);
  }


  private function ActionRestDelete($aParams) {
    // Set error if table not found
    $aTableConfig = $this->GetTableConfig();
    // Check table level access
    if (!$this->Idfix->Access($this->Idfix->cTableName . '_d')) {
      $this->ExitWithError(self::RESPONSE_UNAUTHORIZED);
    }
    // Check if the record we are trying to delete realy belongs to this table
    $aOldRecord = $this->IdfixStorage->LoadRecord($this->Idfix->iObject);
    if (!isset($aOldRecord['MainID'])) {
      $this->ExitWithError(self::RESPONSE_NOT_FOUND);
    }

    if ($aOldRecord['TypeID'] != $aTableConfig['id']) {
      $this->ExitWithError(self::RESPONSE_BAD_REQUEST);
    }

    // Well..... get rid of it...
    $iObjectsRemoved = $this->IdfixAction->DeleteRecurse($this->Idfix->iObject);
    $this->SendBack(array('Objects deleted' => $iObjectsRemoved));
  }


  /**
   * Send back the full configuration for reference
   * 
   * @param mixed $aParams
   * @return void
   */
  private function ActionRestConfig($aParams) {
    $this->SendBack($this->Idfix->aConfig);
  }

  /**
   * Support functions
   */
  private function GetTableConfig() {
    if (isset($this->Idfix->aConfig['tables'][$this->Idfix->cTableName])) {
      return $this->Idfix->aConfig['tables'][$this->Idfix->cTableName];
    }
    else {
      $this->ExitWithError(self::RESPONSE_BAD_REQUEST);
    }
  }

  private function AllowedFieldListForPut($aTableconfig) {
    $aReturn = $this->GetPermittedFields($aTableconfig, 'e');

    // Some fields need to be blacklisted
    // because they are systemfields
    unset($aReturn['TypeID']);
    unset($aReturn['ParentID']);
    unset($aReturn['UidCreate']);
    unset($aReturn['UidChange']);
    unset($aReturn['TSCreate']);
    unset($aReturn['TSChange']);
    unset($aReturn['data']);

    return $aReturn;
  }
  /**
   * IdfixRest::AllowedFieldListForPost()
   * 
   * For adding records we also check if we are allowed to edit
   * No edit allowed, no adding allowed to for that field.
   * 
   * @param mixed $aTableconfig
   * @return
   */
  private function AllowedFieldListForPost($aTableconfig) {
    $aReturn = $this->GetPermittedFields($aTableconfig, 'e');

    // Some fields need to be blacklisted
    // because they are systemfields
    unset($aReturn['MainID']); // Force a new record
    unset($aReturn['UidCreate']);
    unset($aReturn['UidChange']);
    unset($aReturn['TSCreate']);
    unset($aReturn['TSChange']);
    unset($aReturn['data']);

    return $aReturn;
  }

  /**
   * All fields we are allowed to send back to 
   * the client for a get request
   * 
   * @param array $aTableconfig
   * @return array List of fields allowed
   */
  private function AllowedFieldListForGet($aTableconfig) {
    $aReturn = $this->GetPermittedFields($aTableconfig, 'v');

    // Always allow for showing the MainID
    // Without this ID, REST services are not usefull..
    $aReturn['MainID'] = 'MainID';

    return $aReturn;
  }

  /**
   * IdfixRest::GetPermittedFields()
   * Give us a list of all fields we are allowed to:
   * v: view
   * e: edit
   * 
   * @param mixed $aTableConfig
   * @param string $cAccessModifier
   * @return
   */
  private function GetPermittedFields($aTableconfig, $cAccessModifier = 'v') {
    $aReturn = array();

    $cTableName = $aTableconfig['_name'];
    foreach ($aTableconfig['fields'] as $cFieldName => $aFieldConfig) {
      // Do we have field level permissions?????
      if (isset($aFieldConfig['permissions']) and $aFieldConfig['permissions']) {
        // And check it accordingly
        $cPermission = $cTableName . '_' . $cFieldName . '_' . $cAccessModifier;
        if (!$this->Idfix->Access($cPermission)) {
          continue;
        }
      }
      $aReturn[$cFieldName] = $cFieldName;
    }

    return $aReturn;
  }

  /**
   * Filter a full set of records...
   * 
   * @param array $aRecords
   * @param array $aAllowedFieldList
   * @return array 
   */
  private function Filterrecords($aRecords, $aAllowedFieldList) {
    foreach ($aRecords as $cKey => &$aValue) {
      $aValue = $this->FilterRecord($aValue, $aAllowedFieldList);
    }
    return $aRecords;
  }

  private function FilterRecord($aRecord, $AllowedFieldList) {
    return array_intersect_key($aRecord, $AllowedFieldList);
  }


  /**
   * Send a JSON packet to the client
   * 
   * @param mixed $aPacket
   * @return void
   */
  private function SendBack($aPacket) {
    //$aPacket['request_time'] = $fTime = round((microtime(true) - $this->iStart) * 1000, 2);
    header('Content-type: application/json');
    echo json_encode($aPacket);
    exit();
  }

  /**
   * Only send an error code
   * 
   * @param mixed $iErrorCode
   * @return void
   */
  private function ExitWithError($iErrorCode) {
    http_response_code($iErrorCode);
    exit();
  }
}
