<?php

class IdfixFieldsInputTextarea extends IdfixFieldsInput {

  public function GetAjax() {
    // Sorry, at this moment we do not support rich text!
    $bRich = (boolean)(isset($this->aData['rich']) and $this->aData['rich']);
    if ($bRich) {
      $this->GetDisplay();
    }
    else {
      $this->bIsAjax = true;
      $cUrl = $this->Idfix->GetUrl('', '', $this->aData['_name'], $this->aData['__MainID'], 0, 'ajaxupdate');
      $this->aData['onchange'] = "ajaxupdatehandler('{$cUrl}', this.value )";
      $this->GetEdit();
    }
  }

  /**
   * IdfixFieldsInputTextarea::GetDisplay()
   * 
   * If we have a rich text editor, display the value as we intended
   * 
   * @return void
   */
  public function GetDisplay() {
    if (isset($this->aData['rich']) and $this->aData['rich']) {
      $this->aData['__DisplayValue'] = $this->aData['__RawValue'];
    }
    else {
      $this->aData['__DisplayValue'] = $this->Clean($this->aData['__RawValue']);
    }

  }

  public function GetEdit() {
    $this->IdfixDebug->Profiler(__method__, 'start');
    // Unique CSS ID
    $cId = $this->GetId();
    // Unique form input element name
    $cName = $this->GetName();
    // Get CSS class for the input element
    $this->SetCssClass('form-control');
    $this->SetDataElement('id', $cId);
    $this->SetDataElement('name', $cName);

    // Set the value, but do not cvlean if we got a rich text editor
    if (isset($this->aData['rich']) and $this->aData['rich']) {
      $cValue = $this->GetValue();
    }
    else {
      $cValue = $this->Clean($this->GetValue());
    }


    // Build the attributelist
    $cAttr = $this->GetAttributes($this->aData);

    // And get a reference to the input element
    $cInput = "<textarea {$cAttr}>{$cValue}</textarea>";

    // Wrap the element in a group if it is required
    //$cInput = $this->WrapRequired($cInput);

    // Get any validation messages
    $cError = $this->Validate();

    if ($this->bIsAjax) {
      $this->aData['__DisplayValue'] = $cInput;
    }
    else {
      $this->aData['__DisplayValue'] = $this->RenderFormElement($this->aData['title'], $this->aData['description'], $cError, $cId, $cInput);
    }


    // Add Rich text editor if needee
    if (isset($this->aData['rich']) and $this->aData['rich']) {
      $cJs = "
             <script src=\"http://js.nicedit.com/nicEdit-latest.js\" type=\"text/javascript\"></script>
             <script type=\"text/javascript\">bkLib.onDomLoaded(function(){
                new nicEditor().panelInstance('{$cId}');
             });</script>
           ";
      $this->aData['__DisplayValue'] .= $cJs;
    }


    $this->IdfixDebug->Profiler(__method__, 'stop');
  }

}
