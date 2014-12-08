<?php

require_once 'IdfixFieldsInputOptionsBase.class.php';

class IdfixFieldsInputSelect extends IdfixFieldsInputOptionsBase {


  public function GetEdit() {
    $this->IdfixDebug->Profiler(__method__, 'start');
    // Unique CSS ID
    $cId = $this->GetId();
    // Unique form input element name
    $cName = $this->GetName();
    // Set the name to an array element
    $this->SetDataElement('name', $cName . '[]');

    // Create a copy of the data array for the attributes
    $aData = $this->aData;
    // No need for this, always a select element
    unset($aData['type']);
    // Build attribute list
    $cAttr = $this->GetAttributes($aData);
    // Input elements
    $cInput = "<select {$cAttr}>";
    // Create an array with all the selected values
    $aSelected = $this->GetValueAsArray();

    if (isset($aData['options']) and is_array($aData['options'])) {
      foreach ($aData['options'] as $cOptionKey => $cOptionValue) {
        // Is this one checked???
        $cSelect = (in_array($cOptionKey, $aSelected)) ? 'selected="selected"' : '';
        $cInput .= "<option {$cSelect} value=\"{$cOptionKey}\">{$cOptionValue}</option>";
      }
    }

    $cInput .= '</select>';

    $cError = $this->Validate();

    // The post value can be an array or a single value depending on the multiple attribute
    if (isset($this->aData['__RawPostValue']) and !is_null($this->aData['__RawPostValue'])) {
      $xPost = $this->aData['__RawPostValue'];
      if (is_array($xPost)) {
        $this->aData['__SaveValue'] = implode(',', $xPost);
      }
      else {
        $this->aData['__SaveValue'] = trim($xPost);
      }
    }

    if ($this->bIsAjax) {
      $this->aData['__DisplayValue'] = $cInput;
    }
    else {
      $this->aData['__DisplayValue'] = $this->RenderFormElement($this->aData['title'], $this->aData['description'], $cError, $cId, $cInput);
    }


    $this->IdfixDebug->Profiler(__method__, 'stop');

    //$this->IdfixDebug->Debug(__method__, get_defined_vars());

  }

}
