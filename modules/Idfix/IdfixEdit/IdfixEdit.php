<?php

//require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
//use google\appengine\api\cloud_storage\CloudStorageTools;

/**
 * Functionality for rendering edity forms and storing POST information
 * 
 */
class IdfixEdit extends Events3Module {
  // Check if it is the correct form we are validating
  private $cCheckSum = null;
  // Should we be rendering the form or should we be validating the form??
  private $bValidationMode = false;
  // Are the any errors detected  in Validationmode???
  private $bErrorsDetected = false;
  // List of groups that have errors attached
  private $aErrorGroups = array();
  // Save all the values in this array
  private $aDataRow = array();

  public function Events3IdfixActionEdit(&$output) {
    // First check access!!!!
    if (!$this->Idfix->Access($this->Idfix->cTableName . '_e')) {
      return;
    }

    //$this->IdfixDebug->Profiler(__method__, 'start');
    // Id of the record we need to edit
    $iMainID = $this->Idfix->iObject;

    // And get to the table configuration
    $cConfigName = $this->Idfix->cConfigName;
    $cTableName = $this->Idfix->cTableName;
    $aTableConfig = $this->Idfix->aConfig['tables'][$cTableName];
    // Fullly loaded record from disk, or new empty record
    //$this->aDataRow = $this->IdfixStorage->LoadRecord($iMainID);
    $this->aDataRow = $this->LoadDataRow($iMainID, $aTableConfig['id']);
    // Trigger procedure and search and replace field values
    $aTableConfig = $this->Idfix->PostprocesConfig($aTableConfig, $this->aDataRow);
    // Create key to check if we are validating the correct form (SALTED!)
    $this->cCheckSum = md5($cConfigName . $cTableName . $iMainID . $this->Idfix->IdfixConfigSalt);
    // Check if we pressed the SAVE button, if not, no need to validate... right??
    $bSavePressed = (isset($_POST['_idfix_save_button']));
    $bCancelPressed = (isset($_POST['_idfix_cancel_button']));
    // If this checksum is present in the POST infomation it means
    // we should be validating and saving the values.
    $this->bValidationMode = ($bSavePressed and isset($_POST['_checksum']) and $_POST['_checksum'] == $this->cCheckSum);
    // Get off of the HTML for the form and while doing that also do some validation
    $cHtmlInputForm = $this->GetHtmlForForm($aTableConfig);


    //$this->IdfixDebug->Debug(__method__ . '-> Save pressed', $bSavePressed);
    //$this->IdfixDebug->Debug(__method__ . '-> Valideren', $this->bValidationMode);
    //$this->IdfixDebug->Debug(__method__ . '-> Errors', $this->bErrorsDetected);
    //$this->IdfixDebug->Debug(__method__ . '-> POST', $_POST);
    //$this->IdfixDebug->Debug(__method__ . '-> Datarow', $this->aDataRow);

    // By now we know if there were no errors and we can save the values
    if ($this->bValidationMode and !$this->bErrorsDetected) {
      // Save
      $this->IdfixStorage->SaveRecord($this->aDataRow);
    }
    // Do we have a push on the cancel button?
    // or did we push the save button and there are no errors?
    // if there are errors we should show the form again!!
    if ($bCancelPressed or ($bSavePressed and !$this->bErrorsDetected)) {
      // Than return to the list
      $cUrl = $this->Idfix->GetSetLastListPage($cTableName);
      //$cUrl = $this->Idfix->GetUrl($cConfigName, $cTableName, '', $iLastPage, null, 'list');
      //header('location: ' . $cUrl);
      $this->Idfix->RedirectInline($cUrl);
    }

    $cPostUrl = $this->Idfix->GetUrl($cConfigName, $cTableName, '', $iMainID, null, 'edit');

    // For GAE we need a temporary URL, because of the file uploads
    if ($this->ev3->GAE_IsPlatform()) {
      require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
      $options = array('gs_bucket_name' => 'ev3');
      $cUploadUrl = google\appengine\api\cloud_storage\CloudStorageTools::createUploadUrl($cPostUrl, $options);

    }
    else {
      $cUploadUrl = $cPostUrl;
    }


    // Now wrap the raw html in it's form tag and add some hidden fields
    $aTemplate = array(
      'iMainID' => $iMainID,
      'cInput' => $cHtmlInputForm,
      'cHidden' => $this->GetHtmlHiddenFields($aTableConfig),
      'cTitle' => $aTableConfig['title'],
      'cDescription' => $aTableConfig['description'],
      'cIcon' => $this->Idfix->GetIconHTML($aTableConfig),
      'cPostUrl' => $cUploadUrl,
      );

    $output = $this->Idfix->RenderTemplate('EditForm', $aTemplate);
    //$this->IdfixDebug->Profiler(__method__, 'stop');
  }


  /**
   * Get the full HTML representation of the form.
   * Take into account that we may need to group the fields 
   * 
   * @param array $aTableConfig
   * @param array $aDataRowFromDisk
   * @return string HTML
   */
  private function GetHtmlForForm($aTableConfig) {
    $cReturn = '';
    if (isset($aTableConfig['groups']) and is_array($aTableConfig['groups'])) {
      $bFirst = true;
      foreach ($aTableConfig['groups'] as $cGroupId => $aGroupConfig) {
        // First get the HTML elements. This is needed because in the next step
        // we need to know about errors.
        $cElements = $this->GetHtmlForInputElements($aTableConfig['fields'], $cGroupId);

        // Are there any errors on this group? Than set the right CSS classes
        // to open the accordion and show it as a danger panel
        $cClass = ' ';
        $cPanelClass = 'panel-default';
        if (count($this->aErrorGroups) > 0) {
          if (isset($this->aErrorGroups[$cGroupId])) {
            $cClass .= 'in';
            $cPanelClass = 'panel-danger';
          }
        }
        elseif ($bFirst and !$this->bValidationMode) {
          $cClass .= 'in';
        }
        //print_r($this->aErrorGroups);
        // Build the template variables
        $aTemplate = array(
          'cElements' => $cElements,
          'cId' => $cGroupId,
          'cTitle' => $aGroupConfig['title'],
          'cDescription' => $aGroupConfig['description'],
          'cIcon' => $this->Idfix->GetIconHTML($aGroupConfig),
          'cClass' => $cClass,
          'cPanelClass' => $cPanelClass,
          );
        $cReturn .= $this->Idfix->RenderTemplate('EditFormGroup', $aTemplate);
        $bFirst = false;
      }
    }
    else {
      $cElements = $this->GetHtmlForInputElements($aTableConfig['fields']);
      $aTemplate = array(
        'cElements' => $cElements,
        'cId' => 'single-group',
        'cTitle' => $aTableConfig['title'],
        'cDescription' => $aTableConfig['description'],
        'cIcon' => $this->Idfix->GetIconHTML($aTableConfig),
        'cClass' => ' in',
        'cPanelClass' => 'panel-default',
        );
      $cReturn .= $this->Idfix->RenderTemplate('EditFormGroup', $aTemplate);
    }
    return $cReturn;
  }

  /**
   * Create HTML representation for an input element
   * 
   * @param array $aFieldList Field configuration from the tableconfig
   * @param array $aDataRowFromDisk Loaded values from the datatable
   * @param string $cGroup Name of the group if we need te return the HTML only for this group
   * @return string HTML
   */
  private function GetHtmlForInputElements($aFieldList, $cGroup = '') {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $cReturn = '';
    foreach ($aFieldList as $cFieldName => $aFieldConfig) {
      $cInput = '';
      // Skip reserved system fields
      if (stripos(';;MainID;TypeID;ParentID;UidCreate;UidChange;TSCreate;TSChange;data;', ';' . $cFieldName . ';')) {
        continue;
      }
      // Skip virtual fields
      if ($aFieldConfig['type'] == 'virtual') {
        continue;
      }

      // If a group is set, and there is no match, we skip this field
      if (isset($aFieldConfig['group']) and $aFieldConfig['group'] != $cGroup) {
        continue;
      }
      // If no group is set, and we need to get items for a group, skip it also
      if (!isset($aFieldConfig['group']) and $cGroup) {
        continue;
      }

      // We know that we have table level access at this point.
      // But do we need to check field-level permissions?????
      $bAllowEdit = true;
      $bCheckPermissions = (isset($aFieldConfig['permissions']) and $aFieldConfig['permissions']);
      if ($bCheckPermissions) {
        $bAllowEdit = $this->Idfix->Access($aFieldConfig['_tablename'] . '_' . $cFieldName . '_e');
      }

      // No need to go on if we do not have  rights
      if (!$bAllowEdit) {
        continue;
      }

      // Optional value from the table
      $xRawValue = (isset($this->aDataRow[$cFieldName]) ? $this->aDataRow[$cFieldName] : null);
      // Optional value from POST
      $xRawPostValue = (isset($_POST[$cFieldName]) ? $_POST[$cFieldName] : null);
      // And maybe from files if it was an upload...??
      $xRawPostValue = (isset($_FILES[$cFieldName]) ? $_FILES[$cFieldName] : $xRawPostValue);

      // Special case for checkboxes. They do not appear in post!!!!
      // So if there are values posted but this field is not in it
      // set some intelligent default. By now that is a numeric zero
      if (is_null($xRawPostValue) and count($_POST) > 0) {
        $xRawPostValue = 0;
      }

      $aFieldConfig['__RawValue'] = $xRawValue;
      $aFieldConfig['__RawPostValue'] = $xRawPostValue;

      // Depending on the the rights we need to display the edit element or the view element
      if ($bAllowEdit) {
        $this->Idfix->Event('EditField', $aFieldConfig);
        // If there is a value to save, we need to keep it....
        // But only in validationmode
        if ($this->bValidationMode and isset($aFieldConfig['__SaveValue'])) {
          $this->aDataRow[$cFieldName] = $aFieldConfig['__SaveValue'];
        }

      }

      // Last but not least check if there were any errors detected
      if ($this->bValidationMode and isset($aFieldConfig['__ValidationError']) and $aFieldConfig['__ValidationError']) {
        // Register there were errors
        $this->bErrorsDetected = true;
        // Do we have a group? Than register there was an error in this group
        if ($cGroup) {
          $this->aErrorGroups[$cGroup] = true;
        }

      }

      $cInput = $aFieldConfig['__DisplayValue'];
      $cReturn .= $cInput;
    }
    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $cReturn;
  }

  private function GetHtmlHiddenFields($aTableConfig) {
    $cReturn = '';
    $cReturn .= $this->GetHiddenField('_checksum', $this->cCheckSum);
    //$cReturn .= $this->GetHiddenField('_tablename', $aTableConfig['_name']);
    //$cReturn .= $this->GetHiddenField('_configname', $this->Idfix->cConfigName);
    //$cReturn .= $this->GetHiddenField('_return', $_SERVER['HTTP_REFERER']);
    $cReturn .= "<button  formnovalidate=\"formnovalidate\" class=\"btn btn-primary\" type=\"submit\" value=\"Save\" name=\"_idfix_save_button\">Save</button>&nbsp;";
    $cReturn .= "<button  formnovalidate=\"formnovalidate\" class=\"btn btn-success\" type=\"submit\" value=\"Cancel\" name=\"_idfix_cancel_button\">Cancel</button>";
    return $cReturn;
  }
  private function GetHiddenField($cName, $cValue) {
    //$cName = $this->Idfix->ValidIdentifier($cName);
    //$cValue = $this->Idfix->ValidIdentifier($cValue);
    return "<input type=\"hidden\" name=\"{$cName}\" value=\"{$cValue}\">";
  }

  private function LoadDataRow($iMainID, $iTypeID) {
    $aReturn = array();
    if ($iMainID) {
      $aReturn = $this->IdfixStorage->LoadRecord($iMainID);
    }
    else {
      $aReturn = array(
        'TypeID' => $iTypeID,
        'ParentID' => $this->Idfix->iParent,
        );
    }
    return $aReturn;
  }
}
