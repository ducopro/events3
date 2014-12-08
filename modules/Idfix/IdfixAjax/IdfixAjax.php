<?php

class IdfixAjax extends Events3Module {

  public function Events3IdfixActionAjaxupdate(&$output) {
    //$this->log('In ajax handler');
    //$this->log($_SERVER['PATH_INFO']);
    //$this->log($_GET);
    //$this->log($_POST);
    //$this->log($_SESSION);

    // Wat moeten we doen?
    // 1. Check permissions
    //    - Edit table allowed???
    //    - field level permissions??
    // 2. Save value
    // 3. Return a json package for success
    $cTablePermission = $this->Idfix->cTableName . '_e';
    if ($this->Idfix->Access($cTablePermission)) {
      if (isset($this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['fields'][$this->Idfix->cFieldName])) {
        $aFieldConfig = $this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['fields'][$this->Idfix->cFieldName];

        // Check field level access if needed
        $bAccess = true;
        if (isset($aFieldConfig['permissions']) and $aFieldConfig['permissions']) {
          // And check it accordingly
          $cPermission = $this->Idfix->cTableName . '_' . $this->Idfix->cFieldName . '_e';
          $bAccess = $this->Idfix->Access($cPermission);
        }


        // Depending on the the rights we need to display the edit element or the view element
        if ($bAccess) {
          // Check if the value is in the set of allowed
          $aFieldConfig['__RawValue'] = null;
          $aFieldConfig['__RawPostValue'] = $_POST['ajaxupdatevalue'];
          // Trigger the correct event
          $this->Idfix->Event('EditField', $aFieldConfig);
          // Now check for validationerrors
          $bError = (boolean)(isset($aFieldConfig['__ValidationError']) and $aFieldConfig['__ValidationError']);
          if ($bError) {
            // Echo the error to the client.
            $cError = $this->RenderErrorMessages($aFieldConfig);
            echo $cError;
            //$this->log($aFieldConfig);
          }

          // If there is a value to save, we need to keep it....
          // If there are no errors ofcourse
          if (!$bError and isset($aFieldConfig['__SaveValue'])) {
            // Now save it
            $aRecord = array(
              'MainID' => $this->Idfix->iObject,
              $this->Idfix->cFieldName => $aFieldConfig['__SaveValue'],
              );
            $this->IdfixStorage->SaveRecord($aRecord);
            //$this->aDataRow[$cFieldName] = $aFieldConfig['__SaveValue'];
          }
        }


      }
    }

    // No need for any processing right now
    //echo 'My unbelievable very long and custom error mnesaage for the ui';
    exit();
  }

  private function RenderErrorMessages($aFieldConfig) {
    $cError = 'An error was encountered trying to save your data.';
    if (isset($aFieldConfig['__ValidationMessages']) and is_array($aFieldConfig['__ValidationMessages'])) {
      // Set a default message
      if (count($aFieldConfig['__ValidationMessages']) <= 0) {
        $aFieldConfig['__ValidationMessages'][] = $cError;
      }
      $cMessages = implode('</li><li>', $aFieldConfig['__ValidationMessages']);
      $cError = "<ul><li>{$cMessages}</li></ul>";
    }
    return $cError;
  }

  /**
   * Add the wrapper HTML to the navigation bar.
   * Used for sending messages to the ui
   * 
   * @param mixed $data
   * @return void
   */
  public function Events3IdfixNavbarAfter(&$data) {
    $data['custom']['idfixajax'] = '
      <a href="#" id="ajaxupdate-spinner" style="display:none;">
         <div id="ajaxupdate-message" class="text-primary">
            <i id="ajaxupdate-icon" class="fa fa-spinner fa-spin"></i>
            Updating ..
         </div>
      </a>';
  }
}
