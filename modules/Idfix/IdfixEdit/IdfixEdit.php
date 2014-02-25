<?php

/**
 * Functionality for rendering edity forms and storing POST information
 * 
 */ 
class IdfixEdit extends Events3Module {
    
    public $oIdfix, $oIdfixStorage;
    
    public function Events3IdfixActionEdit(){
        $this->oIdfix = $this->load('Idfix');
        $this->oIdfixStorage = $this->load('IdfixStorage');
        $iMainID = $this->oIdfix->iObject;
        $aDataRowFromDisk = $this->oIdfixStorage->LoadRecord($iMainID);
        $cConfigName = $this->oIdfix->cConfigName;
        $cTableName = $this->oIdfix->cTableName;
        $aTableConfig = $this->oIdfix->aConfig['tables'][$cTableName];
        $aFieldList = $aTableConfig['fields'];
        
        foreach($aFieldList AS $cFieldName => $aFieldConfig) {
            $aFieldConfig['__RawValue'] = $aDataRowFromDisk[$cFieldName];
            $this->oIdfix->Event('EditField', $aFieldConfig);
            $cInput = $aFieldConfig['__DisplayValue'];
            echo $cInput;
        }  
        
    }
}