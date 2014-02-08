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
class Events3 {

    // Singleton pattern
    private static $_oInstance = null;
    // True for development, false for producton
    public $bDebug = FALSE;
    private $_aInstances = array();
    // Unique list of available modules
    private $_aModuleList = array();
    // Unique list of modules by event
    private $_aEventList = array();
    // Filecache for the modulelist
    public $bEventFileCache = false;

    public function __construct() {
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
    public function Run() {
        if ($this->bDebug) {
            error_reporting(E_ALL);
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
    public function Test() {
        error_reporting(0);

        // Initialize testing (optional)
        $this->Raise('PreTest');
        // Run tests
        $this->Raise('Test');
        // Cleanup environment (optional)
        $this->Raise('PostTest');
    }

    /**
     * Events3::Raise()
     *
     * First argument should be the case sensitive event name
     * followed by an optional number of extra parameters
     *
     * @return void
     */
    public function Raise() {
        $aParams = func_get_args();
        $sEvent = array_shift($aParams);
        $aModuleList = (array) $this->_aEventList[$sEvent];
        $sEventName = 'Events3' . $sEvent;
        foreach ($aModuleList as $cModulePath) {
            $oModule = $this->LoadModule($cModulePath);
            call_user_func_array(array($oModule, $sEventName), $aParams);
        }
    }

    /**
     * Load a single module
     * 
     * @param string $cModuleName
     * @return object Instantiaded Module/Class
     */
    public function LoadModule($cModuleName) {
        $cModulePath = $cModuleName;

        //print_r($this->_aModuleList);
// Is it a plain modulename?
        if (isset($this->_aModuleList[$cModuleName])) {
            $cModulePath = $this->_aModuleList[$cModuleName];
        }

        // Lazy loading!!!
        if (!array_key_exists($cModulePath, $this->_aInstances)) {
            $cModuleName = basename($cModulePath);
            $cModuleFile = $cModulePath . '/' . $cModuleName . '.php';
            if (is_readable($cModuleFile)) {
                include_once $cModuleFile;
                $this->_aInstances[$cModulePath] = new $cModuleName;
            }
        }

        $oModule = NULL;
        if (is_object($this->_aInstances[$cModulePath])) {
            $oModule = $this->_aInstances[$cModulePath];
        }

        return $oModule;
    }

    /**
     * Scan and save all the modules and events in the system
     * @return void
     */
    private function BuildModuleList() {

        // Implement file caching
        if ($this->bEventFileCache) {
            // Regenerate cache every minute
            $iTimeStamp = (integer) (time() / 60);
            $sFileName = sys_get_temp_dir() . '/Events3EventCache.' . $iTimeStamp . '.tmp';
            if (is_readable($sFileName)) {
                $aCache = (array) unserialize(file_get_contents($sFileName));
                $this->_aModuleList = (array) $aCache['modules'];
                $this->_aEventList = (array) $aCache['events'];
                if (count($this->_aEventList) > 0) {
                    return;
                }
            }
        }

        // Scan all the module paths recursive for all the packages
        $this->_aModuleList = array();
        $aLibraries = $this->GetModulePaths();
        foreach ($aLibraries as $sPath) {
            $this->_getModuleListRecursive($sPath);
        }

        // Now scan all the packages for the events the implement
        foreach ($this->_aModuleList as $cModulePath) {
            $cModuleName = basename($cModulePath);
            $cModuleFile = $cModulePath . '/' . $cModuleName . '.php';
            // Create the entry in the main modulelist
            $this->_aModuleList[$cModuleName] = $cModulePath;
            if (is_readable($cModuleFile)) {
                include_once $cModuleFile;
                $aMethods = get_class_methods($cModuleName);
                foreach ($aMethods as $cMethodName) {
                    if (strpos($cMethodName, 'Events3') === 0) {
                        $cEventName = substr($cMethodName, 7);
                        $this->_aEventList[$cEventName][] = $cModulePath;
                    }
                }
            }
        }

        // Write file cache
        if (!$this->bEventFileCache) {
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
    private function _getModuleListRecursive($sDir) {
        $aList = glob($sDir . '/*', GLOB_ONLYDIR);
        foreach ($aList as $sPath) {
            $this->_aModuleList[basename($sPath)] = $sPath;
            $this->_getModuleListRecursive($sPath);
        }
    }

    /**
     * @return array List of paths where modules can be found
     */
    private function GetModulePaths() {
        // @todo implement addon paths
        return array('modules', 'lib');
    }

    /**
     * Events3::GetHandler()
     *
     * @return instance of Events3
     */
    static function GetHandler() {
        if (is_null(self::$_oInstance)) {
            self::$_oInstance = new Events3();
        }
        return self::$_oInstance;
    }

}

/**
 * Use this class as a baseclass for your modules
 */
class Events3Module {

    /**
     * Simple function to load and return a module instance
     * @param string $cMod Name of the module
     * @return object|null Instantiated module
     */
    public function load($cMod) {
        $ev3 = Events3::GetHandler();
        return $ev3->LoadModule($cMod);
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
class Events3TestCase extends Events3Module {

    private static $iStartTime = null;
    private static $oAssertList = array();
    private static $iAssertCountTotal = 0;
    private static $iAssertCountFailed = 0;

    public function __construct() {
        if (is_null(self::$iStartTime)) {
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
    public function assert($bShouldBeTrue, $cMessage = 'Assertion failed') {
        self::$iAssertCountTotal++;
        if (!$bShouldBeTrue) {
            $this->AddAssertFailed($cMessage);
        }
    }

    private function AddAssertFailed($cMessage) {
        self::$iAssertCountFailed++;
        $cClassName = get_class();

        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $caller = array_shift($bt);
        //print_r($caller);
        
        $file_line = $caller['file'] . "(Line: {$caller['line']}) -> "; 
        $cClassName = get_class( $caller['object']);
        self::$oAssertList[$cClassName][] = $file_line . $cMessage;
    }

    public function __destruct() {
        $iTotalMilliSeconds = round((microtime(true) - self::$iStartTime) * 1000, 2);
        $iTotal = self::$iAssertCountTotal;
        echo "{$iTotal} assertions ran in {$iTotalMilliSeconds} m.s.<br />";
        if (self::$iAssertCountFailed) {
            $iFailed = self::$iAssertCountFailed;
            echo "{$iFailed} of them failed<br /><br />";
            foreach (self::$oAssertList as $cClassName => $aMessages) {
                echo "<b>{$cClassName}</b><br />";
                echo implode('<br/>', $aMessages) . '<br />';
            }
        }
    }

}



;
