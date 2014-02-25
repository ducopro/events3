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

        // Do we need an optional icopn???
        $cIconLib = $this->oIdfix->aConfig['iconlib'];
        $cIcon = $this->GetIcon($cIconLib);
        if ($cIcon) {
            $cValue = $cIcon . '&nbsp' . $cValue;
        }

        // Get all the attributes
        $aAttr = $this->aData;
        $aAttr['role'] = 'button';
        $cAttr = $this->GetAttributes($this->aData);
        

        $cClass = $cReturn = "<a {$cAttr}>{$cValue}</a>";
        
        $this->aData['__DisplayValue'] = $cReturn;
    }


    /**
     * Return the correct html for the image icon
     * 
     * @param mixed $cIconLib
     * @return
     */
    private function GetIcon($cIconLib)
    {
        $cReturn = '';
        $cIcon = $this->aData['icon'];
        if ($cIconLib == 'bootstrap' and $cIcon)
        {
            $cReturn = "<span class=\"glyphicon glyphicon-{$cIcon}\"></span>";
        } elseif ($cIconLib and $cIcon)
        {
            $src = $cIconLib . '/' . $cIcon;
            $cReturn = "<img align=\"absmiddle\" height=\"16\" width=\"16\" src=\"{$src}\" />";
        }
        return $cReturn;
    }
}
