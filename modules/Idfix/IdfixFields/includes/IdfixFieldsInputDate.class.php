<?php

class IdfixFieldsInputDate extends IdfixFieldsInput {

  public function SetData($aData) {
    // Set a good default value
    if (isset($aData['value']) and strtolower($aData['value']) == 'now') {
      $aData['value'] = date('Y-m-d');
    }
    
    //$this->IdfixDebug->Debug(__method__, $aData);
    parent::SetData($aData);
  }

  public function GetDisplay() {
    // New code!!!!
    $iTimestamp =  $this->aData['__RawValue'];
    $cFormat = 'Y-m-d';
    if (isset($this->aData['format']) and $this->aData['format']) {
      $cFormat = $this->aData['format'];
    }
    $this->aData['__DisplayValue'] = date($cFormat, $iTimestamp);
    return;
    // END New code!!!!
    
    // Check formatting options
    $aData = $this->aData;
    //$cValue = $this->Clean($this->aData['__RawValue']);
    $cValue = (integer) $this->aData['__RawValue'];
    
    if (isset($this->aData['format']) and $this->aData['format'] and $cValue) {

      $timestamp = strtotime($this->aData['__RawValue']);

      if (!$timestamp) {
        $timestamp = (integer)$this->aData['__RawValue'];
      }


      $cValue = date($this->aData['format'], $timestamp);
    }
    //$this->IdfixDebug->Debug(__method__, get_defined_vars());
    $this->aData['__DisplayValue'] = $cValue;
  }

  public function GetEdit() {
    // The raw value from the datastore is a timestamp, reformat it to a date
    if (isset($this->aData['__RawValue']) and !is_null($this->aData['__RawValue'])){
      $this->aData['__RawValue'] = date('Y-m-d',$this->aData['__RawValue']);
    }

    parent::GetEdit();
    
    // Postprocess to timestamp
    if(isset( $this->aData['__SaveValue'])) {
       $this->aData['__SaveValue'] = strtotime( $this->aData['__SaveValue']);
    }
    
  }

}
