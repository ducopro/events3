<?php

/**
 * Idfix File Parser Test cases
 */ 
 
 class TestIdfixParse extends Events3TestCase {
    
    public function Events3Test() {
       $oIdfix = $this->load('Idfix');
       $oIdfixParse = $this->load('IdfixParse');
       
       // save old things
       $cConfigNameOld = $oIdfix->cConfigName;
       $aConfigOld = $oIdfix->aConfig;
       
       $oIdfix->cConfigName = 'unittest';
       $oIdfixParse->Events3IdfixGetConfig(); 
       
       $aConfigToCheck = $oIdfix->aConfig;
       $this->assert( is_array($aConfigToCheck));
       $this->assert( isset($aConfigToCheck['name']));
       $this->assert( isset($aConfigToCheck['description']));
       $this->assert( isset($aConfigToCheck['iconlib']));
       $this->assert( is_array($aConfigToCheck['tables']));
       
       //Restore all settings
       $oIdfix->cConfigName = $cConfigNameOld;
       $oIdfix->aConfig = $aConfigOld;
       
    }
 }