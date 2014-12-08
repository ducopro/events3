<?php

require_once 'IdfixFieldsInputOptionsBase.class.php';

class IdfixFieldsInputCheckbox extends IdfixFieldsInputOptionsBase {

  public function GetAjax() {
    $this->bIsAjax = true;
    // $this->log($this->aData);
    // Construct the ajax update URL
    $cUrl = $this->Idfix->GetUrl('','',$this->aData['_name'],$this->aData['__MainID'],0,'ajaxupdate');
    $this->aData['onchange'] = "ajaxupdatehandler('{$cUrl}', this.checked?1:0 )";
    // Get the input element
    $this->GetEdit();
  }


  public function GetEdit() {
    $this->IdfixDebug->Profiler(__method__, 'start');

    // If so, set some extra styling on the element
    $cAjaxStyling = ($this->bIsAjax ? 'style="margin:0px;"' : '');


    // Unique CSS ID
    $cId = $this->GetId();
    // Unique form input element name
    $cName = $this->GetName();

    // Remove the default form-control class!!!
    $this->aData['class'] = str_ireplace('form-control', '', $this->aData['class']);
    $this->SetDataElement('id', $cId);
    $this->SetDataElement('name', $cName);

    // Set the value always to one as an options array
    // for a checkbox should be always 0 and 1
    $bChecked = (boolean)$this->GetValue();
    if ($bChecked) {
      $this->aData['checked'] = 'checked';
    }
    else {
      unset($this->aData['checked']);
    }
    $this->aData['value'] = '1';

    // Build the attributelist
    $cAttr = $this->GetAttributes($this->aData);

    // And get a reference to the input element
    $cInput = $this->GetInputElement($cAttr, $this->aData['title'], $cAjaxStyling);


    // If required we must check it
    $cError = $this->Validate();
    // Only store 0 and 1
    $this->aData['__SaveValue'] = (isset($this->aData['__RawPostValue']) and $this->aData['__RawPostValue']) ? '1' : '0';

    // If we are doing ajax, we only need the input element
    if ($this->bIsAjax) {
      $this->aData['__DisplayValue'] = $cInput;
    }
    else {
      $this->aData['__DisplayValue'] = $this->RenderFormElement('', $this->aData['description'], $cError, $cId, $cInput);
    }


    $this->IdfixDebug->Profiler(__method__, 'stop');


  }

  private function GetInputElement($cAttr, $cTitle, $cAttrDiv = '') {
    return "<div {$cAttrDiv} class=\"checkbox\"><label><input {$cAttr}>{$cTitle}</label></div>";
  }
}
