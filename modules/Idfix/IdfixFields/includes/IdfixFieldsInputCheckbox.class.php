<?php

require_once 'IdfixFieldsInputOptionsBase.class.php';

class IdfixFieldsInputCheckbox extends IdfixFieldsInputOptionsBase
{


    public function GetEdit()
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
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
        if ($bChecked)
        {
            $this->aData['checked'] = 'checked';
        } else
        {
            unset($this->aData['checked']);
        }
        $this->aData['value'] = '1';

        // Build the attributelist
        $cAttr = $this->GetAttributes($this->aData);

        // And get a reference to the input element
        $cInput = $this->GetInputElement($cAttr, $this->aData['title']);
        
      
        // If required we must check it
        $cError = $this->Validate();
        // Only store 0 and 1
        $this->aData['__SaveValue'] = ($this->aData['__RawPostValue']) ? '1' : '0';


        $this->aData['__DisplayValue'] = $this->RenderFormElement('', $this->aData['description'], $cError, $cId, $cInput);
        $this->IdfixDebug->Profiler(__method__, 'stop');


    }

    private function GetInputElement($cAttr, $cTitle)
    {
        return "<div class=\"checkbox\"><label><input {$cAttr}>{$cTitle}</label></div>";
    }
}
