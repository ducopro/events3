<?php

/**
 * This module is just a wrapper for 
 * for the object oriented field system
 */
class IdfixFields extends Events3Module
{

    public function Events3IdfixDisplayField(&$aData)
    {
        //print_r($aData);
        $cType = $aData['type'];
        $cAction = 'Display';
        $this->_factory($cType, $cAction, $aData);
    }

    public function Events3IdfixEditField(&$aData)
    {
        $cType = $aData['type'];
        $cAction = 'Edit';
        $this->_factory($cType, $cAction, $aData);
    }

    public function Events3IdfixValidateField(&$aData)
    {
        $cType = $aData['type'];
        $cAction = 'Validate';
        $this->_factory($cType, $cAction, $aData);
    }

    private function _factory($cType, $cAction, &$aData)
    {
        $this->IdfixDebug->Profiler( __METHOD__, 'start');
        $cDefaultClass = 'IdfixFieldsInput';

        // Let's see if we have a specific implementation
        $cOverride = $cDefaultClass . ucfirst($cType);
        $cOverrideFile = dirname(__file__) . '/includes/' . $cOverride . '.class.php';
        if (is_readable($cOverrideFile))
        {
            include_once $cOverrideFile;
        }

        // Get a reference to the right object
        if (class_exists($cOverride))
        {
            $oField = $cOverride::GetInstance();
        } else
        {
            $oField = $cDefaultClass::GetInstance();
        }

        //echo ($oField);

        // Add a Idfix reference
        $oField->oIdfix = $this->load('Idfix');
        // Import the data structure
        $oField->SetData($aData);
        // Call the right method
        $cMethod = 'Get' . ucfirst($cAction);
        $oField->$cMethod();
        // And return the modified datastructure
        $aData = $oField->GetData();
        $this->IdfixDebug->Profiler( __METHOD__, 'stop');
    }

}


/**
 * Baseclass for a simple field.
 * Fields need to be able to do the following things:
 * - Output Display value
 * - Output HTML for editing
 * - Read and validate POST information
 * 
 */
class IdfixFieldsBase
{
    private static $aInstances = array();
    protected $aData = array();
    public $oIdfix = null;

    static function GetInstance()
    {
        $class = get_called_class();
        if (!isset(self::$aInstances[$class]))
        {
            self::$aInstances[$class] = new $class;
        }
        return self::$aInstances[$class];
    }

    public function SetData($aData)
    {
        $this->aData = $aData;
    }
    public function GetData()
    {

        return $this->aData;
    }

    public function GetDisplay()
    {
    }
    public function GetEdit()
    {
    }
    public function GetValidate()
    {
    }

    /**
     * Cleanup any string for output
     * 
     * 
     * @param mixed $cText
     * @return
     */
    public function Clean($cText)
    {
        return htmlspecialchars($cText, ENT_QUOTES, 'UTF-8');
    }

    public function GetAttributes($aData)
    {
        $cReturn = '';
        $aBlackList = array(
            'title',
            'icon',
            'action',
            '_tablename',
            '_name',
            'confirm',
            //'value',
            '__RawValue',
            '__DisplayValue');
        foreach ($aBlackList as $cBlackKey)
        {
            unset($aData[$cBlackKey]);
        }
        foreach ($aData as $cKey => $cValue)
        {
            if (!is_array($cValue))
            {
                $cReturn .= $this->oIdfix->ValidIdentifier($cKey) . '="' . str_replace('"', "'", (string )$cValue) . '" ';
            }
        }
        return $cReturn;
    }

    /**
     * Use this function to set elements in the data array
     * 
     * @param mixed $cElementName
     * @param mixed $cElementValue
     * @return void
     */
    public function SetDataElement($cElementName, $cElementValue)
    {
        if (isset($this->aData[$cElementName]))
        {
            $this->aData[$cElementName] .= ' ' . trim($cElementValue);
        } else
        {
            $this->aData[$cElementName] = $cElementValue;
        }
    }

    /**
     * Return a valid name for an html element
     * 
     * @return
     */
    public function GetName()
    {
        return $this->oIdfix->ValidIdentifier($this->aData['_tablename'] . '-' . $this->aData['_name']);
    }

    protected function RenderEditElement($cRawInput, $cId)
    {
        $cReturn = "<div class=\"form-group\">";
        if (isset($this->aData['title']) and $this->aData['title'])
        {
            $cReturn .= "<label for=\"{$cId}\">{$this->aData['title']}</label>";
        }
        $cReturn .= $cRawInput;
        if (isset($this->aData['description']) and $this->aData['description'])
        {
            $cReturn .= "<p class=\"help-block\">{$this->aData['description']}</p>";
        }
        $cReturn .= '</div>' . "\n";
        return $cReturn;
    }
}

/**
 * The most basic fieldtype is a standard 
 * HTML5 input field
 * 
 */
class IdfixFieldsInput extends IdfixFieldsBase
{
    public function GetDisplay()
    {
        parent::GetDisplay();
        $this->aData['__DisplayValue'] = $this->Clean($this->aData['__RawValue']);
    }

    /**
     * Give u a simple string defining the HTML for an Input element
     * 
     * @return void
     */
    public function GetEdit()
    {
        $cId = $this->GetName();
        $this->SetDataElement('name', $cId);
        $this->SetDataElement('class', 'form-control');

        $aData = $this->aData;
        $aData['value'] = $this->Clean($aData['__RawValue']);
        $cAttr = $this->GetAttributes($aData);
        $cInput = "<input {$cAttr}>";
        $this->aData['__DisplayValue'] = $this->RenderEditElement($cInput, $cId);
    }


}
