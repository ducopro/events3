<?php

/**
* Templating module.
* Code borrowed from DRUPAL 
* 
* Always use dirname(__FILE__) to build the 
* full path to the specified template in the calling module
* 
*/

class Template extends Events3Module{
	
	public function Render( $cTemplateFile, $aVariables = array()){
		extract($aVariables, EXTR_SKIP); // Extract the variables to a local namespace
		ob_start(); // Start output buffering
		include "$cTemplateFile"; // Include the template file
		$contents = ob_get_contents(); // Get the contents of the buffer
		ob_end_clean(); // End buffering and discard
		return $contents; // Return the contents
	}
	
}