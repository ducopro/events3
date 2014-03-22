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
        if (!isset($_GET['idfix'])) {
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
        $navbar = $this->RenderNavBar();
        // Get all the rendered messages
        $messages = $this->FlashMessage();

        // And wrap them in the body HTML
        $cBodyContent = $this->RenderTemplate('Idfix', array(
            'content' => $content,
            'navbar' => $navbar,
            'messages' => $messages));
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
        $this->cConfigName = $cConfigName;
        $this->cTableName = $cTableName;
        $this->cFieldName = $cFieldName;
        $this->cAction = ucfirst($this->ValidIdentifier($cAction));
        $this->iObject = intval($iObject);
        $this->iParent = intval($iParent);


        // Try to create the configuration from cache
        $cConfigCacheKey = 'GetCachedConfig_' . $this->cConfigName;
        $this->aConfig = $this->GetSetCache($cConfigCacheKey);
        // Check if we got one...
        if (!is_array($this->aConfig)) {
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

        // Call an extra event for postprocessing
        $this->Event('Render', $output);

        return $output;
    }

    /**
     * Wrapper for generating the navigation system
     * 
     * @return string Rendered NavigationBar
     */
    private function RenderNavBar()
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        // Create the empty Navigationbar structure
        // Brand = The leftmost, biogger name of the system
        // left = structure floated to the left
        // right = structure floated tio the right
        $aNavBar = array(
            'brand' => array(),
            'left' => array(),
            'right' => array());
        // Call the event system to fill it
        $this->Event('Navbar', $aNavBar);
        // Than render it to HTML
        $cNavBar = $this->RenderTemplate('IdfixNavbar', array('navbar' => $aNavBar));
        // Stop profiling
        $this->IdfixDebug->Profiler(__method__, 'stop');
        // Return the rendered navigation bar
        return $cNavBar;
    }

    /**
     * Do a nice redirect to another page while maintaining the
     * session and also the flash messages.
     * 
     * @param mixed $cUrl
     * @return void
     */
    public function Redirect($cUrl)
    {
        session_write_close();
        header('location: ' . $cUrl);
        exit(0);
    }
    /**
     * Isfix event handler for the navigation bar
     * 
     * @param mixed $data
     * @return void
     */
    public function Events3IdfixNavbar(&$data)
    {
        // First set the name of the application
        $data['brand']['title'] = $this->aConfig['title'];
        $data['brand']['href'] = $this->GetUrl('', '', '', null, null, 'info');
        $data['brand']['tooltip'] = $this->aConfig['description'];
        $data['brand']['icon'] = $this->GetIconHTML($this->aConfig);

        // Add a link for adding a new record
        $bAccess = $this->Access($this->cTableName . '_a');
        if (isset($this->aConfig['tables'][$this->cTableName]) and $this->cAction == 'List' and $bAccess) {
            $data['left'][''] = array(
                'title' => 'New ' . $this->aConfig['tables'][$this->cTableName]['title'],
                'tooltip' => $this->aConfig['tables'][$this->cTableName]['description'],
                'href' => $this->GetUrl($this->cConfigName, $this->cTableName, '', 0, $this->iParent, 'edit'),
                'icon' => $this->GetIconHTML('plus'),
                );
        }


        // Create a dropdown structure for showing all the tables in the system
        // Temporary code shows ALL the tables, not only top level
        $aDropdown = array();
        foreach ($this->aConfig['tables'] as $cTableName => $aTableConfig) {
            // Be sure we do not have child objects
            if ($this->TableIsTopLevel($cTableName) and $this->Access($cTableName . '_v')) {
                $aDropdown[$cTableName] = array(
                    'title' => isset($aTableConfig['title']) ? $aTableConfig['title'] : '',
                    'href' => $this->GetUrl($this->cConfigName, $cTableName, '', 1, 0, 'list'), // top level list
                    'tooltip' => isset($aTableConfig['description']) ? $aTableConfig['description'] : '',
                    'icon' => $this->GetIconHTML($aTableConfig),
                    'active' => ($cTableName == $this->cTableName),
                    'type' => 'normal',
                    );
            }
        }

        // List the main Tables in the system
        $data['left']['tables'] = array(
            'title' => 'Tables',
            'tooltip' => 'Select one of the top-level tables in the system.',
            'href' => '#',
            'dropdown' => $aDropdown,
            'icon' => $this->GetIconHTML('tasks'),
            );


        // Powered By Idfix
        $data['right']['system'] = array(
            'title' => 'Powered by Idfix',
            'tooltip' => 'Agile PHP Cloud Development Platform',
            'href' => $this->GetUrl($this->cConfigName, '', '', 0, 0, 'Controlpanel'), // top level list
            'icon' => '',
            );
    }

    /**
     * Check to see if we have a top level table
     * 
     * @param mixed $cTableName
     * @return boolean true if it is a top level table
     */
    private function TableIsTopLevel($cTableName)
    {
        $bRetval = true;

        if (is_array($this->aConfig['tables'])) {
            foreach ($this->aConfig['tables'] as $table_sub_name => $aTableConfig) {
                if (isset($aTableConfig['childs']) and is_array($aTableConfig['childs'])) {
                    // Check if the tablename itself is in it's child list
                    // If that's the case we have inheritance and the table must
                    // be top level
                    $is_inherit = in_array($table_sub_name, $aTableConfig['childs']);
                    if (in_array($cTableName, $aTableConfig['childs']) and !$is_inherit) {
                        $bRetval = false;
                        break;
                    }
                }
            }
        }

        return $bRetval;
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
        // Prefix all events with Idfix
        $cEventName = 'Idfix' . $cEventName;
        $this->IdfixDebug->Profiler(__method__ . '::' . $cEventName, 'start');
        // Raise three events
        $this->ev3->Raise($cEventName . 'Before', $xValue);
        $this->ev3->Raise($cEventName, $xValue);
        $this->ev3->Raise($cEventName . 'After', $xValue);
        // Stop profiling
        $this->IdfixDebug->Profiler(__method__ . '::' . $cEventName, 'stop');
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
        $cTemplateFile = dirname(__file__) . "/templates/{$cTemplateName}.php";
        $cReturn = $this->Template->Render($cTemplateFile, $aVars);
        return $cReturn;
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
     * @param string $cAttributes Optiobnal exctra info to append to the querystring
     * @return
     */
    public function GetUrl($cConfigName = '', $cTablename = '', $cFieldName = '', $iObject = null, $iParent = null, $cAction = '', $aAttributes = array())
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        $cConfigName = $cConfigName ? $cConfigName : $this->cConfigName;
        $cTablename = $cTablename ? $cTablename : $this->cTableName;
        $cFieldName = $cFieldName ? $cFieldName : $this->cFieldName;
        $iObject = !is_null($iObject) ? $iObject : $this->iObject;
        $iParent = !is_null($iParent) ? $iParent : $this->iParent;
        $cAction = $cAction ? $cAction : $this->cAction;

        // Create an array of querystring parameters
        $aAttributes['idfix'] = "{$cConfigName}/{$cTablename}/{$cFieldName}/{$iObject}/{$iParent}/{$cAction}";
        // Now let other modules add default values to it if needed
        // See the sort module for information
        $this->Event('GetUrl', $aAttributes);
        // Create the concatenated querystring
        $cQueryString = http_build_query($aAttributes);
        // But decode it, because there might be variables in there for postprocessing!!!
        $cQueryString = urldecode($cQueryString);
        //$cQueryString = implode('&',$aAttributes);
        // Build the Url
        $cUrl = "index.php?{$cQueryString}";
        $this->IdfixDebug->Profiler(__method__, 'stop');
        return $cUrl;
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
        if ($blacklist) {
            $cKey = str_replace(str_split($blacklist), '_', $cKey);
        }
        if (is_numeric(substr($cKey, 0, 1))) {
            $cKey = '_' . $cKey;
        }
        $this->IdfixDebug->Profiler(__method__, 'stop');
        return $cKey;
    }


    /**
     * Check access restrictions
     * 
     * @param mixed $cPermission
     * @return
     */
    public function Access($cPermission)
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        //$args = func_get_args();
        $bAccess = true;
        // Put all the values in the array
        $aPack = compact('bAccess', 'cPermission');
        // And send them to the event handler
        $this->Event('Access', $aPack);
        // Now only extract the access value
        $bAccess = (boolean)$aPack['bAccess'];
        $this->IdfixDebug->Profiler(__method__, 'stop');
        return $bAccess;

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
        // Special case if we send in a configuration structure
        if (is_array($cIcon)) {
            if (isset($cIcon['icon'])) {
                $cIcon = $cIcon['icon'];
            }
            else {
                $cIcon = '';
            }
        }

        if (substr($cIcon, 0, 4) == 'http') {
            return "<img align=\"absmiddle\" src=\"{$cIcon}\">&nbsp;";
        }
        elseif ($cIcon and strtolower($this->aConfig['iconlib']) == 'bootstrap') {
            return "<span class=\"glyphicon glyphicon-{$cIcon}\"></span>&nbsp;";
        }
        elseif ($cIcon) {
            $cIcon = $this->aConfig['iconlib'] . '/' . $cIcon;
            return "<img align=\"absmiddle\" height=\"16\" width=\"16\" src=\"{$cIcon}\">&nbsp;";
        }

        return '';
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

        // S H O R T C U T
        // If it is a field, the configuration preprocessor already analyzed
        // if this field needs postprocessing. In that case we can skip this
        // expensive effort.....
        if (isset($aConfig['_NoPP']) and $aConfig['_NoPP']) {
            //echo 2;
            $this->IdfixDebug->Profiler(__method__, 'stop');
            return $aConfig;
        }

        if (is_array($aConfig)) {
            foreach ($aConfig as &$aConfig_element) {
                // 1. Get dynamic display values, option elements!!!!
                if ((is_string($aConfig_element) and (stripos($aConfig_element, '%%') !== false))) {
                    $aConfig_element = $this->DynamicDisplayValues($aConfig_element, $aRecord);
                }
                
                // 2. Get normal dynamic values to parse?
                if (is_string($aConfig_element) and (stripos($aConfig_element, '%') !== false)) {
                    $aConfig_element = $this->DynamicValues($aConfig_element, $aRecord);
                }
                // 3. Plain array? Recursive action
                elseif (is_array($aConfig_element)) {
                    $aConfig_element = $this->PostprocesConfig($aConfig_element, $aRecord);
                }
            }
        }

        $this->IdfixDebug->Profiler(__method__, 'stop');
        return $aConfig;
    }

    /**
     * Idfix::DynamicDisplayValues()
     * 
     * Use the double procents with care. There is a lot of processing involved here :-(
     * 
     * @param mixed $aHaystack
     * @param mixed $aValues
     * @return void
     */
    private function DynamicDisplayValues($aHaystack, $aValues)
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        if (is_array($aValues)) {
            // We need to know the table configuration first
            $aTableConfig = $this->TableConfigById($aValues['TypeID']);
            // Now process all the values
            foreach ($aValues as $cKey => $xValue) {
                $search = '%%' . $cKey . '%%';
                if (strpos($aHaystack, $search) !== false) {
                    // Ok, now we know the this key is a fieldname and should be
                    // replaced by the displayvalue, but first we need to get
                    // the full field configuration
                    if (isset($aTableConfig['fields'][$cKey])) {
                        // The fieldconfiguration
                        $aFieldConfig = $aTableConfig['fields'][$cKey];
                        // Set the value to prosess
                        $aFieldConfig['__RawValue'] = $xValue;
                        // Call the weventhandler on the field
                        $this->Idfix->Event('DisplayField', $aFieldConfig);
                        // Set the value back in the variable
                        $xValue = $aFieldConfig['__DisplayValue'];
                        // And do a search and replace on the element
                        $aHaystack = str_replace($search, $xValue, $aHaystack);
                    }
                }
            }
        }
        $this->IdfixDebug->Profiler(__method__, 'stop');
        return $aHaystack;
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
        $this->IdfixDebug->Profiler(__method__, 'start');
        if (is_array($aValues)) {
            foreach ($aValues as $cKey => $xValue) {
                $search = '%' . $cKey . '%';
                if (strpos($aHaystack, $search) !== false) {
                    $aHaystack = str_replace($search, $xValue, $aHaystack);
                }
            }
        }
        $this->IdfixDebug->Profiler(__method__, 'stop');
        return $aHaystack;
    }

    /**
     * Set a message to show on the next page
     * 
     * @param string $cMessage
     * @param string $cType = (success,info,warning,danger)
     * @return string rendered messages if no parameters are sent
     */
    public function FlashMessage($cMessage = null, $cType = 'success')
    {
        if (is_null($cMessage)) {
            $cMessages = '';
            // Render the messages
            if (isset($_SESSION[__method__]) and is_array($_SESSION[__method__])) {
                foreach ($_SESSION[__method__] as $cType => $aMessages) {
                    $cMessages .= $this->RenderTemplate('FlashMessages', compact('cType', 'aMessages'));
                }
            }
            // Clear the mesaage queu
            unset($_SESSION[__method__]);
            return $cMessages;
        }
        else {
            // Set new message
            $_SESSION[__method__][$cType][] = $cMessage;
        }
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
        $cUrl = '';
        $cSessionKey = '__Idfix__LastListPage_';
        // Set Value
        if (!$cTableName and $this->cAction == 'List') {
            $cUrl = $this->GetUrl();
            $_SESSION[$cSessionKey] = $cUrl;
        }
        // Get value
        else {
            if (isset($_SESSION[$cSessionKey])) {
                $cUrl = $_SESSION[$cSessionKey];
            }
        }

        return $cUrl;
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
        if ($this->IdfixConfigCache) {
            // Reset the cache
            if (is_null($cKey)) {
                unset($_SESSION[self::CACHE_KEY]);
            }
            // Try to get a value from the cache
            elseif (is_null($xValue)) {
                // Check if there is a value
                if (isset($_SESSION[self::CACHE_KEY][$cKey])) {
                    // Than set the return value
                    $xValue = unserialize($_SESSION[self::CACHE_KEY][$cKey]);
                }
            }
            // OK, we must set the cache
            else {
                $_SESSION[self::CACHE_KEY][$cKey] = serialize($xValue);
            }
        }
        else {
            // Well, no need for caching, so we can just as well clean it up
            // and save some space and memory
            if (isset($_SESSION[self::CACHE_KEY])) {
                unset($_SESSION[self::CACHE_KEY]);
            }

        }
        // Stop profiling
        $this->IdfixDebug->Profiler(__method__, 'stop');

        return $xValue;
    }

    /**
     * Trail()
     *
     * @param
     *   integer $iMainId
     * @return
     *   Array with the current trail
     */
    public function Trail($iMainId)
    {
        // return cached trail if set
        static $aStaticCache = array();
        if (isset($aStaticCache[$iMainId])) {
            return $aStaticCache[$iMainId];
        }

        $aTrail = array();

        $next_id = $iMainId;
        while ($next_id) {
            $aRecord = $this->IdfixStorage->LoadRecord($next_id);
            $aTrail[$aRecord['MainID']] = $aRecord['TypeID'];
            $next_id = $aRecord['ParentID'];
        }

        // Store the trail for later use
        $aStaticCache[$iMainId] = $aTrail;

        return $aTrail;

    }

    /**
     * Idfix::BreadCrumbs()
     * 
     * @param mixed $iMainId
     * @return string Fully rendered OL list for showing breadcrumbs from the current MainID
     */
    public function BreadCrumbs($iMainId)
    {
        $aBaseTrail = $this->Trail($iMainId);
        $aTrail = array();


        // New functionality based on the API trail function
        foreach ($aBaseTrail as $iTrailMainId => $iTrailTypeId) {
            $aRecord = $this->IdfixStorage->LoadRecord($iTrailMainId);
            $aTrail[] = $this->BreadCrumbItem($aRecord, $iTrailTypeId);
        }

        $cItems = implode('', array_reverse($aTrail));
        return "<ol class=\"breadcrumb\">{$cItems}</ol>";
    }

    /**
     * Idfix::BreadCrumbItem()
     * 
     * @param array $aRecord Record from the idfix table
     * @param integer $iTypeId Unique identifier of the table
     * @return string List item for the breadcrumb trail
     */
    private function BreadCrumbItem($aRecord, $iTypeId)
    {
        $aConfig = $this->TableConfigById($iTypeId);
        $aConfig = $this->PostprocesConfig($aConfig, $aRecord);
        if (isset($aConfig['trail']) and $aConfig['trail']) {
            $cTitle = $aConfig['trail'];
        }
        else {
            $cTitle = $aConfig['title'] . ' ' . $aRecord['MainID'];
        }

        $cUrl = $this->GetUrl('', $aConfig['_name'], '', 1, $aRecord['ParentID'], 'list');
        $cReturn = "<li><a href=\"{$cUrl}\">{$cTitle}</a></li>";

        return $cReturn;
    }


    /**
     * Idfix::TableConfigById()
     * 
     * @param integer $iTypeId
     * @return array Table Configuration
     */
    private function TableConfigById($iTypeId)
    {
        static $aStaticCache = array();
        if (isset($aStaticCache[$iTypeId])) {
            return $aStaticCache[$iTypeId];
        }
        //$this->IdfixDebug->Profiler(__method__, 'start');

        $cReturn = array();
        $aConfig = $this->aConfig;
        foreach ($aConfig['tables'] as $cTableName => $aTableConfig) {
            if ($aTableConfig['id'] == $iTypeId) {
                $cReturn = $aTableConfig;
                break;
            }
        }

        $aStaticCache[$iTypeId] = $cReturn;
        //$this->IdfixDebug->Profiler(__method__, 'stop');
        return $cReturn;
    }

}
