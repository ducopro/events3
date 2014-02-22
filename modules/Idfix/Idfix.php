<?php

/**
 * The Idfix module is the dispathhing engine for building
 * a modular system.
 * 
 */
class Idfix extends Events3Module
{
    // Command line parameter values
    public $aConfig, $cConfigName, $cTableName, $cFieldName, $iObject, $iParent, $cAction;


    /**
     * Configuration settings that can be overruled
     * in the configurationfile
     * 
     * @param array &$aConfig Reference to the configuration array
     * @return void
     */
    public function Events3ConfigInit(&$aConfig)
    {
        $key = 'IdfixConfigProfiler';
        $aConfig[$key] = isset($aConfig[$key]) ? $aConfig[$key] : 'off';
        $this->$key = $aConfig[$key];
    }

    /**
     * Run event and main entry for the IDFIX system
     * 
     * @param string $cIdfixCommands, Commandline parameters tos use instead of the values from $_GET
     * @return void
     */
    public function Events3Run()
    {
        // Default values from the url
        $cCommand = urldecode((string )@$_GET['idfix']);
        // What do we need to do?
        $aInput = explode('/', $cCommand);

        // This way we have some intelligent defaults
        // which may be empty of course
        $cConfigName = (string )array_shift($aInput);
        $cTableName = (string )array_shift($aInput);
        $cFieldName = (string )array_shift($aInput);
        $iObject = (integer)array_shift($aInput);
        $iParent = (integer)array_shift($aInput);
        $cAction = (string )array_shift($aInput);

        echo $this->Render($cConfigName, $cTableName, $cFieldName, $iObject, $cAction);
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
    public function Render($cConfigName, $cTableName, $cFieldName, $iObject, $cAction)
    {
        $this->cConfigName = $this->ValidIdentifier($cConfigName);
        $this->cTableName = $this->ValidIdentifier($cTableName);
        $this->cFieldName = $this->ValidIdentifier($cFieldName);
        $this->cAction = ucfirst($this->ValidIdentifier($cAction));
        $this->iObject = intval($iObject);

        // Create an empty configuration array
        $this->aConfig = array();

        // Fill the config array
        $this->Event('GetConfig');

        // Check if datatables are present
        $this->CheckEnvironment();

        // Create the output variable
        $output = '';
        // Create the Main EventName
        $cEventName = 'Action' . $this->cAction;
        // ... and call the main event
        $this->Event($cEventName, $output);

        return $output;
    }

    public function Event($cEventName, &$xValue = '')
    {
        /* @var $events3 Events3 */
        $ev3 = Events3::GetHandler();

        // Prefix all events with Idfix
        $cEventName = 'Idfix' . $cEventName;
        // Raise three events
        $ev3->Raise($cEventName . 'Before', $xValue);
        $ev3->Raise($cEventName, $xValue);
        $ev3->Raise($cEventName . 'After', $xValue);
    }


    /**
     * Support functions
     * 
     */

    /**
     * Render an Idfix template
     * 
     * @param string $cTemplateName Name of the template without path and extention
     * @param array $aVars Template variables (@see Template Module)
     * @return string Rendered template
     */
    public function RenderTemplate($cTemplateName, $aVars = array())
    {
        // Add reference to idfix to the template
        $aVars['oIdfix'] = &$this;
        /* @var $oTemplate Template*/
        $oTemplate = $this->load('Template');
        $cTemplateFile = dirname(__file__) . "/templates/{$cTemplateName}.php";
        return $oTemplate->Render($cTemplateFile, $aVars);
    }

    public function GetUrl($cConfigName = '', $cTablename = '', $cFieldName = '', $iObject = null, $iParent = null, $cAction = '')
    {
        $cConfigName = $cConfigName ? $cConfigName : $this->cConfigName;
        $cTablename = $cTablename ? $cTablename : $this->cTableName;
        $cFieldName = $cFieldName ? $cFieldName : $this->cFieldName;
        $iObject = !is_null($iObject) ? $iObject : $this->iObject;
        $iParent = !is_null($iParent) ? $iParent : $this->iParent;
        $cAction = $cAction ? $cAction : $this->cAction;
        return "index.php?idfix={$cConfigName}/{$cTablename}/{$cFieldName}/{$iObject}/{$iParent}/{$cAction}";
    }
    public function ValidIdentifier($key)
    {
        $key = strtolower($key);
        $blacklist = str_replace(str_split('abcdefghijklmnopqrstuvwxyz_1234567890'), '', $key);
        if ($blacklist)
        {
            $key = str_replace(str_split($blacklist), '_', $key);
        }
        if (is_numeric(substr($key, 0, 1)))
        {
            $key = '_' . $key;
        }
        return $key;
    }
    
    /**
     * Check if there is access to this field of the configuration
     * @todo
     * 
     * @param string $cConfigName
     * @param string $cTableName
     * @param string $cFieldName
     * @param string $cOp Allowed values: view, edit, add, delete
     * @return
     */
    public function FieldAccess($cConfigName,$cTableName, $cFieldName, $cOp ) {
        //$args = func_get_args();
        $bAccess = false;
        // Put all the values in the array
        $aPack = Get_defined_vars();
        // And send them to the event handler
        $this->Event('Access', $aPack);
        // Now only extract the access value
        $bAccess = (boolean) $aPack['bAccess'];
        return $bAccess;
    }
    /**
     * Stub for the access handling.
     * Access is always allowed.
     * @see also the IdfixUser module which imnplements access handling
     * 
     * @param bollean reference $bAccess
     * @param string $cConfigName
     * @param string $cTableName
     * @param string $cFieldName
     * @param string $cOp
     * @return void
     */
    public function Events3IdfixAccess(&$aPack) {
        //print_r($aPack);
        $aPack['bAccess'] = true;
    }
    
    /**
     * Cleanup any string for output
     * 
     * 
     * @param mixed $cText
     * @return
     */
    public function CleanOutputString($cText)
    {
        return htmlspecialchars($cText, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Private support functions
     * 
     */


    private function CheckEnvironment()
    {
        $db = $this->load('IdfixStorage');
        $db->Check();
    }


}
