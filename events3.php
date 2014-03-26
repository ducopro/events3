<?php

// NO ERROR reporting
error_reporting(0);

/**
 * @file
 * events3.php
 *
 * Event Handler & Module Loader
 *
 * Events3 can be used to build web and cli applications
 * It is not an MVC framework but.... it can be used to build one
 *
 * Basicly Events3 is an events dispatcher. But because events always
 * translate to excecuting code, we need a way to determine what code
 * to run.
 * So eventhandling and module loading should be tightly
 * integrated according to Events3.
 *
 * Events3 is strictly modular. Add-on modules should be placed
 * in the modules subdirectory, although there is and event
 * that allwos you to specify other locations.
 *
 * Add-on Modules:
 *
 * For an Example module that implements the Run event for displaying Hello World n the web browser
 *
 * ./modules/Example.php
 *
 * class Example Extends Events3Module {
 *
 *   public method Events3Run {
 *     echo 'Hello World';
 *   }
 *
 * }
 *
 *
 */
class Events3
{
    // Path to the Events3 System
    public $BasePath, $BasePathUrl;
    public $PublicPath, $PublicPathUrl;
    public $PrivatePath;
    
    // Configuration file
    public $ConfigFile;
    // Singleton pattern
    private static $_oInstance = null;
    // True for development, false for producton
    public $bDebug = false;
    // Set to true when in UNIT testmode
    public $bTest = false;
    private $_aInstances = array();
    // Unique list of available modules
    private $_aModuleList = array();
    // Unique list of modules by event
    private $_aEventList = array();
    // Filecache for the modulelist
    public $bEventFileCache = false;

    public function __construct($bUnitTestMode = false)
    {
        
        $this->bTest = $bUnitTestMode;
        $this->BasePath = dirname(__file__);
        $this->BasePathUrl = '//' . dirname( $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']);
        $this->PublicPath = $this->BasePath . '/files';
        $this->PublicPathUrl = str_ireplace( $this->BasePath, $this->BasePathUrl, $this->PublicPath);
        $this->PrivatePath = $this->BasePath . '/../files';
        $this->ConfigFile = dirname(__file__) . '/config/config.ini';
        //print_r($this);
        // Scan all modules and build the lists
        $this->BuildModuleList();
    }

    /**
     * Events3::Run()
     *
     * Start the eventhandling sequence
     *
     * @return void
     */
    public function Run()
    {
        if ($this->bDebug)
        {
            error_reporting(E_ALL);
            register_shutdown_function('Events3Shutdown');
        }
        // Initialize modules
        $this->Raise('PreRun');
        // Basic functionality
        $this->Raise('Run');
        // Cleanup
        $this->Raise('PostRun');
    }

    /**
     * Events3::Test()
     *
     * Start the testing sequence
     *
     * @return void
     */
    public function Test()
    {
        error_reporting(E_ALL);

        // Initialize modules
        $this->Raise('PreRun');
        // Initialize testing (optional)
        $this->Raise('PreTest');
        // Run tests
        $this->Raise('Test');
        // Cleanup environment (optional)
        $this->Raise('PostTest');
        // Cleanup
        $this->Raise('PostRun');
    }

    /**
     * Events3::Raise()
     *
     * First argument should be the case sensitive event name
     * followed by an optional number of extra parameters
     *
     * @return void
     */
    public function Raise($sEvent, &$xParam = null)
    {
        // Get generic parameters
        $aParams = func_get_args();
        // Strip the first two .. we already have them
        array_shift($aParams);
        array_shift($aParams);
        // Now add the first parameter again, but by reference
        //$aNewParams = array( &$xParam );
        $aNewParams = array_merge(array(&$xParam), $aParams);
        //print_r($aNewParams);
        // By now, the parameter array is filled with a first
        // element by reference and the optional rest by value

        // Now create an array with the modules
        if (isset($this->_aEventList[$sEvent]))
        {
            $aModuleList = $this->_aEventList[$sEvent];
            $sEventName = 'Events3' . $sEvent;
            foreach ($aModuleList as $cModulePath)
            {
                $oModule = $this->LoadModule($cModulePath);
                call_user_func_array(array($oModule, $sEventName), $aNewParams);
            }
        }
    }

    /**
     * Load a single module
     * 
     * @param string $cModuleName
     * @return object Instantiaded Module/Class
     */
    public function LoadModule($cModuleName)
    {
        $cModulePath = $cModuleName;

        // Is it a plain modulename?
        if (isset($this->_aModuleList[$cModuleName]))
        {
            $cModulePath = $this->_aModuleList[$cModuleName];
        }

        // Lazy loading!!!
        if (!array_key_exists($cModulePath, $this->_aInstances))
        {
            $cModuleName = basename($cModulePath);
            $cModuleFile = $cModulePath . '/' . $cModuleName . '.php';
            if (is_readable($cModuleFile))
            {
                include_once $cModuleFile;
                $this->_aInstances[$cModulePath] = new $cModuleName;
            }
        }

        $oModule = null;
        if ( isset($this->_aInstances[$cModulePath]) and is_object($this->_aInstances[$cModulePath]))
        {
            $oModule = $this->_aInstances[$cModulePath];
        }

        return $oModule;
    }

    /**
     * Scan and save all the modules and events in the system
     * @return void
     */
    private function BuildModuleList()
    {
        // Regenerate cache every minute
        $iTimeStamp = (integer)(time() / 60);
        $sFileName = sys_get_temp_dir() . '/Events3EventCache.' . $iTimeStamp . '.tmp';

        // Implement file caching
        if ($this->bEventFileCache)
        {
            if (is_readable($sFileName))
            {
                $aCache = (array )unserialize(file_get_contents($sFileName));
                $this->_aModuleList = (array )$aCache['modules'];
                $this->_aEventList = (array )$aCache['events'];
                if (count($this->_aEventList) > 0)
                {
                    return;
                }
            }
        }

        // Scan all the module paths recursive for all the packages
        $this->_aModuleList = array();
        $aLibraries = $this->GetModulePaths();
        foreach ($aLibraries as $sPath)
        {
            $this->_getModuleListRecursive($sPath);
        }

        // Now scan all the packages for the events the implement
        foreach ($this->_aModuleList as $cModulePath)
        {
            $cModuleName = basename($cModulePath);
            $cModuleFile = $cModulePath . '/' . $cModuleName . '.php';
            // Create the entry in the main modulelist
            $this->_aModuleList[$cModuleName] = $cModulePath;
            include_once $cModuleFile;
            $aMethods = (array )get_class_methods($cModuleName);
            foreach ($aMethods as $cMethodName)
            {
                if (strpos($cMethodName, 'Events3') === 0)
                {
                    $cEventName = substr($cMethodName, 7);
                    $this->_aEventList[$cEventName][] = $cModulePath;
                }
            }

        }

        // Write file cache
        if (!$this->bEventFileCache)
        {
            $aCache = array(
                'modules' => $this->_aModuleList,
                'events' => $this->_aEventList,
                );
            file_put_contents($sFileName, serialize($aCache));
        }
    }

    /**
     * @param string $sDir Path to scan
     */
    private function _getModuleListRecursive($sDir)
    {
        $aList = glob($sDir . '/*', GLOB_ONLYDIR);
        foreach ($aList as $sPath)
        {
            // Check if we need to scan the unittest modules
            $cModName = basename($sPath);
            if (!$this->bTest and (substr($cModName, 0, 4) == 'Test'))
            {
                continue;
            }
            // It is only a module if there is a specific file
            if (is_readable($sPath . '/' . $cModName . '.php'))
            {
                $this->_aModuleList[$cModName] = $sPath;
            }

            $this->_getModuleListRecursive($sPath);
        }
    }

    /**
     * @return array List of paths where modules can be found
     */
    private function GetModulePaths()
    {
        // @todo implement addon paths
        return array('modules', 'lib');
    }

    /**
     * Events3::GetHandler()
     *
     * @return instance of Events3
     */
    static function GetHandler($bUnitTestMode = false)
    {
        if (is_null(self::$_oInstance))
        {
            self::$_oInstance = new Events3($bUnitTestMode);
        }
        return self::$_oInstance;
    }

}

/**
 * Use this class as a baseclass for your modules
 */
class Events3Module
{

    // Reference to the framework
    protected $ev3;

    public function __construct()
    {
        $this->ev3 = Events3::GetHandler();
    }
    /**
     * Simple function to load and return a module instance
     * @param string $cMod Name of the module
     * @return object|null Instantiated module
     */
    public function load($cMod)
    {
        $ev3 = Events3::GetHandler();
        return $ev3->LoadModule($cMod);
    }

    /**
     * By way of this magic method we can get
     * to the module instance just bij accessing
     * it as a property.
     * 
     * @example $this->Template->Render()
     * where Template is the module name 
     * 
     * @param mixed $cModuleName
     * @return
     */
    public function __get($cModuleName)
    {
        return $this->load($cModuleName);
    }

}

/**
 * Use this class as a baseclass for your modules that implement
 * unittesting for you module
 * 
 * Example:
 * 
 * /Example/Example.php                   extends Events3Module
 *         /TestExample/TestExample.php   extends Events3TestCase
 * 
 * 
 * 
 * 
 */
class Events3TestCase extends Events3Module
{

    private static $iStartTime = null;
    private static $oAssertList = array();
    private static $iAssertCountTotal = 0;
    private static $iAssertCountFailed = 0;

    public function __construct()
    {
        parent::__construct();
        if (is_null(self::$iStartTime))
        {
            self::$iStartTime = microtime(true);
        }
    }

    /**
     * Test an assertion and store some information about the assertions
     * that ran.
     * 
     * @param boolean $bShouldBeTrue
     * @param string $message
     */
    public function assert($bShouldBeTrue, $cMessage = 'Assertion failed')
    {
        self::$iAssertCountTotal++;
        if (!$bShouldBeTrue)
        {
            $this->AddAssertFailed($cMessage);
        }
    }

    private function AddAssertFailed($cMessage)
    {
        self::$iAssertCountFailed++;

        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $caller = array_shift($bt);
        //print_r($caller);

        // What is the name of the testclass?
        $cClassName = get_class($caller['object']);

        // Let's get the codeline that is responsible for the error
        $aContent = file($caller['file']);
        $iCodeLine = (int)$caller['line'] - 1;
        $cCodeLine = trim($aContent[$iCodeLine]);
        //print_r($aContent);

        // Now get the relative path
        $cOriginalFileName = $caller['file'];
        $cThisFilePath = dirname(__file__);
        $cRelativeFilePath = str_ireplace($cThisFilePath, '', $cOriginalFileName);

        // Add the relative filepath to the classname
        $cClassName .= "  ({$cRelativeFilePath})";

        // Build the line to display the error
        $file_line = "{$cMessage} -> {$caller['line']}: {$cCodeLine}";

        self::$oAssertList[$cClassName][] = $file_line;
    }

    public function __destruct()
    {
        static $bRunOnce = false;
        // We only need to display output one time
        if ($bRunOnce)
        {
            return;
        }
        $bRunOnce = true;

        // Determine what type of line-break to use
        $cLb = (PHP_SAPI == 'cli') ? "\n" : '<br />';
        $iTotalMilliSeconds = round((microtime(true) - self::$iStartTime) * 1000, 2);
        $iTotal = self::$iAssertCountTotal;

        echo "----------------" . $cLb;
        echo "Events3 UnitTest" . $cLb;
        echo "----------------" . $cLb . $cLb;

        echo "{$iTotal} assertions ran in {$iTotalMilliSeconds} m.s." . $cLb;
        if (self::$iAssertCountFailed)
        {
            $iFailed = self::$iAssertCountFailed;
            echo "{$iFailed} of them failed." . $cLb;
            foreach (self::$oAssertList as $cClassName => $aMessages)
            {
                echo "{$cLb}## {$cClassName}{$cLb}";
                echo implode($cLb, $aMessages) . $cLb;
            }
        } else
        {
            echo "All tests completed without failures.";

        }
    }

}

function Events3Shutdown()
{
    $aErrors = error_get_last();
    if (is_array($aErrors))
    {
        echo "<h2>PHP ERROR</h2>-- Error: {$aErrors['type']}\n<br />-- Message: {$aErrors['message']}\n<br />-- File:  {$aErrors['file']}\n<br />-- Line:  {$aErrors['line']}\n<br />";
    }
}
