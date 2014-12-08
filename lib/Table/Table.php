<?php

/**
 * Module for rendering an html table
 */
class Table extends Events3Module {
    private $aRows = array();
    private $cHeader = '';
    
    /**
     * Public Interface
     */ 
    
    public function SetRow($aData, $aAttributes = array()){
        $cRow = '';
        foreach($aData as $xContent) {
            $cRow .= $this->GetCell($xContent);
        }
        $this->aRows[] = $this->GetHtmlElement('tr', $cRow, $this->CreateAttributes($aAttributes));
        
    }
    public function SetHeader($aData, $aAttributes = array()){
        $this->cHeader = '';
        foreach($aData as $xContent) {
            $this->cHeader .= $this->GetHtmlElement('th', $xContent);
        }
        $this->cHeader = $this->GetHtmlElement('tr', $this->cHeader, $this->CreateAttributes($aAttributes));
    }
    public function GetTable($aAttributes = array()){
        $cData = $this->cHeader;
        foreach($this->aRows as $cRow) {
            $cData .= $cRow;
        }
        // reset rows
        $this->aRows = array();
        return $this->GetHtmlElement('table', $cData, $this->CreateAttributes($aAttributes));        
    }
    
    /**
     * Private Methods
     */ 
     private function GetCell($xData) {
        $cContent = $xData;
        $aAttribs = array();
        if(is_array($xData)) {
            $cContent = $xData['data'];
            unset($xData['data']);
            $aAttribs = $xData;
        }
        $cAttribs = $this->CreateAttributes($aAttribs);
        return $this->GetHtmlElement('td', $xData, $cAttribs);
     }
     
     private function GetHtmlElement($cElement, $cContent, $cAttributes = '') {
        // Add space to the attributes
        if ($cAttributes) {
            $cAttributes = ' ' . trim($cAttributes);
        }
        return "<{$cElement}{$cAttributes}>{$cContent}</{$cElement}>";
     }
     
     private function CreateAttributes($aAttr) {
        $cReturn = '';
        foreach($aAttr as $cAttribName => $cAttribValue) {
            $cReturn .= " {$cAttribName}=\"{$cAttribValue}\"";
        }
        return $cReturn;
     }
}