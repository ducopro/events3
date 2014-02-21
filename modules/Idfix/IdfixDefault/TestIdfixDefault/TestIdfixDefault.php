<?php

/**
 * Idfix File Parser Test cases
 */ 
 
 class TestIdfixDefault extends Events3TestCase {
    
    public function Events3Test() {
       $oIdfix = $this->load('Idfix');
       $oIdfixDefault = $this->load('IdfixDefault');
       
       // save old things
       $cConfigNameOld = $oIdfix->cConfigName;
       $aConfigOld = $oIdfix->aConfig;

       $aVeryBasicConfig = array(
         'tables' => array(
            'table1' => array(
              'name' => 'Table #1',
              'fields' => array(
                 'field1' => array(
                    'name' => 'Field #1',
                    'type' => 'textfield', 
                 ),
              ),
            ),
         ),
       );
       
       $oIdfix->cConfigName = 'basic_config';
       $oIdfix->aConfig = $aVeryBasicConfig;
       $oIdfixDefault->Events3IdfixGetConfigAfter();
       
       $aTest = $oIdfix->aConfig;
       $this->assert( isset($aTest['title']));
       $this->assert( isset($aTest['tablespace']));
       $this->assert( isset($aTest['tables']));
       $this->assert( is_array($aTest['tables']));
       
       
       //Restore all settings
       $oIdfix->cConfigName = $cConfigNameOld;
       $oIdfix->aConfig = $aConfigOld;
       
    }
 }