<?php

require_once 'IdfixFieldsInputOptionsBase.class.php';

class IdfixFieldsInputCheckboxes extends IdfixFieldsInputOptionsBase {

  public function GetAjax() {
    $this->bIsAjax = false;
    $this->GetDisplay();
  }

  public function GetEdit() {
    $this->IdfixDebug->Profiler(__method__, 'start');
    // Unique CSS ID
    $cId = $this->GetId();
    // Unique form input element name
    $cName = $this->GetName();

    // Remove the default form-control class!!!
    $this->aData['class'] = str_ireplace('form-control', '', $this->aData['class']);
    $this->SetDataElement('name', $cName . '[]');
    // Do we need inline checkboxes???
    $cInline = (isset($this->aData['inline']) and $this->aData['inline']) ? 'checkbox-inline' : '';

    // Create an array with all the selected values
    $aSelected = $this->GetValueAsArray();
    // Create a copy of the data array for the attributes
    $aData = $this->aData;
    $cInput = '';


    foreach ($aData['options'] as $cOptionKey => $cOptionValue) {
      // Set correct type
      $aData['type'] = 'checkbox';
      // What value to set if checked????
      $aData['value'] = $cOptionKey;
      // Is this one checked???
      unset($aData['checked']);
      if (in_array($cOptionKey, $aSelected)) {
        $aData['checked'] = 'checked';
      }
      // Build the attributelist
      $cAttr = $this->GetAttributes($aData);
      // Add to the input element
      $cInput .= $this->GetInputElement($cAttr, $cOptionValue, $cInline);

    }

    // If we have inline elements we need to wrap them in a div
    $cInput = ($cInline) ? "<div>{$cInput}</div>" : $cInput;


    $cError = $this->Validate();

    if (is_array($this->aData['__RawPostValue'])) {
      $this->aData['__SaveValue'] = implode(',', $this->aData['__RawPostValue']);
    }


    $this->aData['__DisplayValue'] = $this->RenderFormElement($this->aData['title'], $this->aData['description'], $cError, $cId, $cInput);
    $this->IdfixDebug->Profiler(__method__, 'stop');

    //$this->IdfixDebug->Debug(__method__, get_defined_vars());

  }

  private function GetInputElement($cAttr, $cTitle, $cInlineClass) {
    $return = "<label class=\"{$cInlineClass}\"><input {$cAttr}>{$cTitle}</label>";
    // If there is no inline class, wrap it in it's own div
    if (!$cInlineClass) {
      $return = "<div class=\"checkbox\">{$return}</div>";
    }
    return $return;

  }
}
