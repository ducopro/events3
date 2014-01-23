<?php

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
        // Initialize modules
        $this->Raise('PreRun');
        // Basic functionality
        $this->Raise('Run');
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
    private function LoadModule($cModuleName) {
        $cModulePath = $cModuleName;
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
            $this->_aModuleList[ basename($sPath) ] = $sPath;            
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
