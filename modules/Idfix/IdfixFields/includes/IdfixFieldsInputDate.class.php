<?php

class IdfixFieldsInputDate extends IdfixFieldsInput
{

    public function SetData($aData)
    {
        // Set a good default value
        if (isset($aData['value']) and strtolower($aData['value']) == 'now')
        {
            $aData['value'] = date('Y-m-d');
        }
        //$this->IdfixDebug->Debug(__method__, $aData);
        parent::SetData($aData);
    }

    public function GetDisplay()
    {
        // Check formatting options
        $cValue = $this->Clean($this->aData['__RawValue']);
        if (isset($this->aData['format']) and $this->aData['format'] and $cValue)
        {
            $timestamp = strtotime($this->aData['__RawValue']);
            $cValue = date($this->aData['format'], $timestamp);
        }
        //$this->IdfixDebug->Debug(__method__, $timestamp);
        $this->aData['__DisplayValue'] = $cValue;
    }

    public function GetEdit()
    {
        parent::GetEdit();
    }

}
