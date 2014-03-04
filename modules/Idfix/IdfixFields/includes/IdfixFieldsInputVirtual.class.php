<?php

class IdfixFieldsInputVirtual extends IdfixFieldsInput
{

    public function GetDisplay()
    {
        
        // Get the default value we need to display in the button
        $cValue = $this->aData['title'];
        if (isset($this->aData['value']) and $this->aData['value'])
        {
            $cValue = $this->aData['value'];
        }

        // Do we need an optional icon???
        if(isset($this->aData['icon']) and $this->aData['icon'] ) {
          $cIconHtml = $this->Idfix->GetIconHTML($this->aData['icon']);
          $cValue = $cIconHtml . '&nbsp' . $cValue;    
        }

        // Do we need conformation??
        if(isset($this->aData['confirm']) and $this->aData['confirm'] ) {
            $cConfirm = $this->aData['confirm'];
            $this->aData['onclick'] = "return confirm('{$cConfirm}')";
        }
        // Get all the attributes
        $cAttr = $this->GetAttributes($this->aData);
        

        $cReturn = "<a {$cAttr}>{$cValue}</a>";
        
        $this->aData['__DisplayValue'] = $cReturn;
    }


   
}
