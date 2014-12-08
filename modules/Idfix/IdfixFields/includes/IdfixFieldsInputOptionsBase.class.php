<?php

class IdfixFieldsInputOptionsBase extends IdfixFieldsInput {

  
  /**
   * Default functionality if we need ajax handling in lists
   * 
   * @return void
   */
  public function GetAjax() {
    $this->bIsAjax = true;
    // $this->log($this->aData);
    // Construct the ajax update URL
    $cUrl = $this->Idfix->GetUrl('', '', $this->aData['_name'], $this->aData['__MainID'], 0, 'ajaxupdate');
    $this->aData['onchange'] = "ajaxupdatehandler('{$cUrl}', this.value )";
    // Get the input element
    $this->GetEdit();
  }
  /**
   * All of these option fields have in common that they have an
   * options array and one or more selected options in a comma separated
   * list.
   * 
   * @return void
   */
  public function GetDisplay() {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $options = $this->aData['options'];
    // Get our values in an array
    $aSelected = $this->GetValueAsArray();
    $aDisplay = array();
    foreach ($aSelected as $xKey) {
      $cDisplay = $xKey;
      // If the key is in the options array we get the user friendly display variant
      if (isset($this->aData['options'][$xKey])) {
        $cDisplay = $this->aData['options'][$xKey];
      }
      $aDisplay[] = $cDisplay;
    }
    $cDisplay = implode(', ', $aDisplay);
    $this->aData['__DisplayValue'] = $cDisplay;
    $this->IdfixDebug->Debug(__method__, get_defined_vars());
    $this->IdfixDebug->Profiler(__method__, 'stop');
  }

  /**
   * Split our input string in an array with all the selected options
   * 
   * @return array
   */
  protected function GetValueAsArray() {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $aValues = array();
    // Our base to work on
    $cValue = $this->GetValue();
    if (!is_null($cValue)) {
      if (stripos($cValue, ',')) {
        $aValues = explode(',', $cValue);
      }
      elseif (strlen($cValue) > 0) {
        $aValues[] = $cValue;

      }

    }
    //$this->IdfixDebug->Debug(__method__, get_defined_vars());
    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $aValues;
  }


}
