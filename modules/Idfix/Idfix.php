<?php

/**
 * The Idfix module is the dispathhing engine for building
 * a modular system.
 * 
 */
class Idfix extends Events3Module {

    public function Events3Run() {
        // What do we need to do?
        $aInput = explode('/', urldecode( (string) @$_GET['idfix']));

        // This way we have some intelligent defaults
        // which may be empty of course
        $cConfigName = (string) array_shift($aInput);
        $cTableName = (string) array_shift($aInput);
        $cFieldName = (string) array_shift($aInput);
        $iObject = (integer) array_shift($aInput);
        $cAction = (string) array_shift($aInput);
        
        return $this->Render($cConfigName, $cTableName, $cFieldName, $iObject, $cAction);
    }

    /**
     * 
     * @param char $cConfigName 
     *   Name of the configuration to show
     * @param char $cTableName 
     *   Name of the table
     * @param char $cFieldName 
     *   Name of the field
     * @param int $iObject
     *   Unique ID of the
     * @param char $cAction
     *   Action to perform
     */
    private function Render($cConfigName, $cTableName, $cFieldName, $iObject, $cAction) {
        /* @var $events3 Events3 */
        $events3 = Events3::GetHandler();
        // Create an empty configuration array
        $config = array();

        // Fill the config array
        $events3->Raise('IdfixGetConfig', $config, $cConfigName);
        // Modify the configuration
        $events3->Raise('IdfixChangeConfig', $config, $cConfigName);

        // Create the output variable
        $output = '';
        // And call the appropriate action events
        $events3->Raise('IdfixActionBefore' . $cAction, $output, $config, $cConfigName, $cTableName, $cFieldName, $iObject);
        $events3->Raise('IdfixAction' . $cAction, $output, $config, $cConfigName, $cTableName, $cFieldName, $iObject);
        $events3->Raise('IdfixActionAfter' . $cAction, $output, $config, $cConfigName, $cTableName, $cFieldName, $iObject);
        
        return $output;
    }

}
