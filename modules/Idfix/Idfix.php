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

    // Stubs for the configuration properties
    // Main purpose for this declaration is code completion.
    public $IdfixConfigSalt, $IdfixConfigCache;

    // Entry in $_SESSION array for caching
    const CACHE_KEY = '__idfix_cache__';


    /**
     * Configuration settings that can be overruled
     * in the configurationfile
     * 
     * @param array &$aConfig Reference to the configuration array
     * @return void
     */
    public function Events3ConfigInit(&$aConfig)
    {
        $cKey = 'IdfixConfigSalt';
        $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : md5(time());
        $this->$cKey = $aConfig[$cKey];

        $cKey = 'IdfixConfigCache';
        $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : 1;
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
        $navbar = $this->RenderTemplate('IdfixNavbar', array('Idfix' => $this));
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


        // Try to create the configuration from cache
        $cConfigCacheKey = 'GetCachedConfig';
        $this->aConfig = $this->GetSetCache($cConfigCacheKey);
        // Check if we got one...
        if (!is_array($this->aConfig))
        {
            // Ok, we need to create it from scratch ....
            $this->Event('GetConfig');
            // Time to to some checking for the correct tables
            // Do it here because we only need to to it once
            $this->IdfixStorage->check();
            // And do not forget to store evertything in cache
            $this->GetSetCache($cConfigCacheKey, $this->aConfig);
        }

        // Create the output variable
        $output = '';
        // Create the Main EventName
        $cEventName = 'Action' . $this->cAction;
        // ... and call the main event
        $this->Event($cEventName, $output);

        return $output;
    }

    /**
     * Main event handling system of Idfix
     * Every events results in 3 events raised on the
     * framework level
     * 
     * @param string $cEventName
     * @param mixed &$xValue Most of the time a data strtucture
     * @return void The datastructure can be manipulated
     */
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

    /**
     * Give us a url to a Idfix page and use some intelligent default values
     * 
     * @param string $cConfigName
     * @param string $cTablename
     * @param string $cFieldName
     * @param integer $iObject
     * @param integer $iParent
     * @param string $cAction
     * @return
     */
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

    /**
     * Create a nice identifier that's stripped from all
     * of the bad characters and leaves us only with
     * - lowercase
     * - digits
     * - alpha
     * - underscore
     * 
     * @param string $cKey
     * @return string Cleaned op $cKey
     */
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
     * This is the main method for checking permissions
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
     * Stub for the access handling. Access is always allowed.
     * This stub is implemented as a BEFORE handler, so it is mneant to
     * be a default value.
     * Other modules could ovberride it in the NORMAL or AFTER handler
     * 
     * @see also the IdfixUser module which imnplements access handling
     * 
     * @param boolean reference $bAccess
     * @param string $cConfigName
     * @param string $cTableName
     * @param string $cFieldName
     * @param string $cOp
     * @return void
     */
    public function Events3IdfixAccessBefore(&$aPack)
    {
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
     * Idfix::GetIconHTML()
     * This method is nessecary because we can have different types of
     * iconlibs.
     * 1. Built in bootstrap icons
     * 2. A remote library
     * 3. Local file library
     * 
     * @param mixed $cIcon Name of the icon
     * @return string Full HTML for display purposes
     */
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
     * $this->DynamicValues()
     * Only called once from the above method :-)
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
     * Workhorse function for caching all kind of things.
     * 
     * We are using session based caching because:
     * 1. It is as fast as static caching
     * 2. Storing and retrieving is done just once when the session
     *   is started and when the the PHP system is closed down
     * 3. By default we have file based sessions and they are read
     *    by PHP itself (C-code) and operating system, mostly LINUX.
     *    Bottomline: as fast as possible
     * 4. We can skip a lot of expensive MySql queries that are normally
     *    done every call to the server.
     * 
     * I know that we are impacting performance by putting the profiler
     * statements in here. But we really want to know the impact on perfomance
     * this cache has. So it is a bit of a tradeoff.
     * 
     * But note that if the profiling system is shut down, there is only a function
     * call performed with no code whatsoever.
     * 
     * @param string $cKey
     * @param mixed $xValue
     * @return mixed Cached $xValue
     */
    public function GetSetCache($cKey = null, $xValue = null)
    {
        // Start profiling
        $this->IdfixDebug->Profiler(__method__, 'start');

        // Only use the cache if it is configurated
        if ($this->IdfixConfigCache)
        {
            // Reset the cache
            if (is_null($cKey))
            {
                unset($_SESSION[self::CACHE_KEY]);
            }
            // Try to get a value from the cache
            elseif (is_null($xValue))
            {
                // Check if there is a value
                if (isset($_SESSION[self::CACHE_KEY][$cKey]))
                {
                    // Than set the return value
                    $xValue = unserialize($_SESSION[self::CACHE_KEY][$cKey]);
                }
            }
            // OK, we must set the cache
            else
            {
                $_SESSION[self::CACHE_KEY][$cKey] = serialize($xValue);
            }
        } else
        {
            // Well, no need for caching, so we can just as well clean it up
            // and save some space and memory
            if (isset($_SESSION[self::CACHE_KEY]))
            {
                unset($_SESSION[self::CACHE_KEY]);
            }
            
        }
        // Stop profiling
        $this->IdfixDebug->Profiler(__method__, 'stop');

        return $xValue;
    }


}
