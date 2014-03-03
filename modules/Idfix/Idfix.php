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
        $cKey = 'IdfixSalt';
        $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : md5(time());
        $this->$cKey = $aConfig[$cKey];
    }

    /**
     * Run event and main entry for the IDFIX system
     * 
     * @param string $cIdfixCommands, Commandline parameters tos use instead of the values from $_GET
     * @return void
     */
    public function Events3Run()
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        // Quick check ...
        if (!isset($_GET['idfix']))
        {
            return;
        }
        //print_r(get_defined_vars());

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

        $content = $this->Render($cConfigName, $cTableName, $cFieldName, $iObject, $iParent, $cAction);
        $navbar = $this->RenderTemplate('IdfixNavbar', array( 'Idfix' => $this) );
        // And wrap them in the body HTML
        $cBodyContent = $this->RenderTemplate('Idfix', array('content' => $content, 'navbar' => $navbar));
        echo $cBodyContent;
        $this->IdfixDebug->Profiler(__method__, 'stop');
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
    public function Render($cConfigName, $cTableName, $cFieldName, $iObject, $iParent, $cAction)
    {
        $this->cConfigName = $this->ValidIdentifier($cConfigName);
        $this->cTableName = $this->ValidIdentifier($cTableName);
        $this->cFieldName = $this->ValidIdentifier($cFieldName);
        $this->cAction = ucfirst($this->ValidIdentifier($cAction));
        $this->iObject = intval($iObject);
        $this->iParent = intval($iParent);


        // Create an empty configuration array
        $this->aConfig = array();

        // Fill the config array
        $this->Event('GetConfig');

        // Check if datatables are present
        $this->IdfixStorage->check();

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
        $this->IdfixDebug->Profiler(__method__, 'start');
        /* @var $events3 Events3 */
        $ev3 = Events3::GetHandler();

        // Prefix all events with Idfix
        $cEventName = 'Idfix' . $cEventName;
        // Raise three events
        $ev3->Raise($cEventName . 'Before', $xValue);
        $ev3->Raise($cEventName, $xValue);
        $ev3->Raise($cEventName . 'After', $xValue);
        $this->IdfixDebug->Profiler(__method__, 'stop');
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
        $this->IdfixDebug->Profiler(__method__, 'start');
        // Add reference to idfix to the template
        $aVars['oIdfix'] = &$this;
        /* @var $oTemplate Template*/
        $oTemplate = $this->load('Template');
        $cTemplateFile = dirname(__file__) . "/templates/{$cTemplateName}.php";

        $return = $oTemplate->Render($cTemplateFile, $aVars);
        $this->IdfixDebug->Profiler(__method__, 'stop');
        return $return;
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
    public function ValidIdentifier($cKey)
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        $cKey = strtolower($cKey);
        $blacklist = str_replace(str_split('abcdefghijklmnopqrstuvwxyz_1234567890'), '', $cKey);
        if ($blacklist)
        {
            $cKey = str_replace(str_split($blacklist), '_', $cKey);
        }
        if (is_numeric(substr($cKey, 0, 1)))
        {
            $cKey = '_' . $cKey;
        }
        $this->IdfixDebug->Profiler(__method__, 'stop');
        return $cKey;
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
    public function FieldAccess($cConfigName, $cTableName, $cFieldName, $cOp)
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        //$args = func_get_args();
        $bAccess = false;
        // Put all the values in the array
        $aPack = Get_defined_vars();
        // And send them to the event handler
        $this->Event('Access', $aPack);
        // Now only extract the access value
        $bAccess = (boolean)$aPack['bAccess'];
        $this->IdfixDebug->Profiler(__method__, 'stop');
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
    public function Events3IdfixAccess(&$aPack)
    {
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

    public function GetIconHTML($cIcon)
    {
        if (strtolower($this->aConfig['iconlib']) == 'bootstrap')
        {
            return "<span class=\"glyphicon glyphicon-{$cIcon}\"></span>&nbsp;";
        } else
        {
            $cIcon = $this->aConfig['iconlib'] . '/' . $cIcon;
            return "<img align=\"absmiddle\" height=\"16\" width=\"16\" src=\"{$cIcon}\">&nbsp;";
        }
    }

    /**
     * PostprocesConfig()

     * Postprocess a (part of a) configuration array for dynamic values
     *
     * Check for:
     * 1. Callbacks (with optional parameters)
     * 2. variables
     *
     * Variables are names surrounded by an % like '%ParenID%'
     * These are substituted with the values from the second
     * parameter to this function: $aRecord
     *
     * @param
     *   mixed $aConfig
     * @param
     *   mixed $aRecord
     * @return
     *   Processed configuration
     */
    public function PostprocesConfig($aConfig, $aRecord = array())
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        if (is_array($aConfig))
        {
            foreach ($aConfig as &$aConfig_element)
            {
                // 1. Callback without parameters
                if (is_string($aConfig_element) and (substr($aConfig_element, 0, 1) == '@') and function_exists(substr($aConfig_element, 1)))
                {
                    $aConfig_element = call_user_func(substr($aConfig_element, 1));

                }
                // 2. Callback with parameters
                elseif (isset($aConfig_element[0]) and (substr($aConfig_element[0], 0, 1) == '@') and function_exists(substr($aConfig_element[0], 1)))
                {
                    // Get the function
                    $cFunctionName = substr(array_shift($aConfig_element), 1);
                    // Now postprocess the parameters for dynamic values
                    $aParameters = $this->PostprocesConfig($aConfig_element, $aRecord);
                    $aConfig_element = call_user_func_array($cFunctionName, $aParameters);

                }
                // 3. Dynamic values to parse?
                elseif (is_string($aConfig_element) and (stripos($aConfig_element, '%') !== false))
                {
                    $aConfig_element = $this->DynamicValues($aConfig_element, $aRecord);
                }

                // 4. Plain array? Recursive action
                elseif (is_array($aConfig_element))
                {
                    $aConfig_element = $this->PostprocesConfig($aConfig_element, $aRecord);
                }
            }
        }
        $this->IdfixDebug->Profiler(__method__, 'stop');
        return $aConfig;
    }

    /**
     * Sometimes we just need to know what was the last page showed
     * in the list.
     * Fort example if we start editing a value. After that we need to go to the 
     * correct page.
     * This is a helper function for that sitaution
     * Call it without parameters to store the last active page.
     * Give it a tablename and it returns the last known page.
     * 
     * @param string $cTableName
     * @return integer PageNumber for the specified table. defaults to 1.
     */
    public function GetSetLastListPage($cTableName = '')
    {
        $iReturn = 1;
        $cSessionKey = '__Idfix__LastListPage';
        // Set Value
        if (!$cTableName)
        {
            $cTableName = $this->cTableName;
            $_SESSION[$cSessionKey][$cTableName] = $this->iObject;
        }
        // Get value
        else
        {
            if (isset($_SESSION[$cSessionKey][$cTableName]))
            {
                $iReturn = (integer)$_SESSION[$cSessionKey][$cTableName];
            }
        }
        return $iReturn;
    }

    /**
     * $this->DynamicValues()
     *
     * @param
     *   mixed $aHaystack
     * @param
     *   mixed $aValues
     * @return
     *   Processed data structure
     */
    private function DynamicValues($aHaystack, $aValues)
    {
        if (is_array($aValues))
        {
            foreach ($aValues as $cKey => $xValue)
            {
                $search = '%' . $cKey . '%';
                if (strpos($aHaystack, $search) !== false)
                {
                    $aHaystack = str_replace($search, $xValue, $aHaystack);
                }
            }
        }
        return $aHaystack;
    }


}
