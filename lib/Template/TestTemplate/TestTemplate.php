<?php

class TestTemplate extends Events3TestCase{
	
	function Events3Test(){
		
		// Test if the template module itself is available
		$oTemplate = $this->load('Template');
		$this->assert( is_object($oTemplate));
		$this->assert(is_a( $oTemplate, 'Events3Module'));
		
		// Test if there is a template file ready for us
		$cTemplateFile = dirname(__FILE__) . '/TestTemplate.html';
		$this->assert( is_readable($cTemplateFile));
		
		// Test if the template is rendered without variables
		$output = $oTemplate->Render($cTemplateFile);
		$this->assert( strpos($output, '<!--TestTemplate-->'));
		
		// Test if variables are rendered in the template
		$cValue = '__$$##@@StringToTest$$##@@';
		$aVars = array('TestTemplate' => $cValue );
		$output = $oTemplate->Render($cTemplateFile, $aVars);
		$this->assert( strpos($output, $cValue) );
		
	}
}