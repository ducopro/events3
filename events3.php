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
// Temporary Developement
$iStart = microtime(true);

// All functionality of the events3 framwork is in this
// handler class. Get the event handler by way of this call.
// It is a singleton pattern implementation
$Events3 = Events3::GetHandler();
// Set some properties. Note that these are the default settings
// and only displayed for demonstrating the basic configuration of the handler
$Events3->bDebug = true;  //
$Events3->Run();

// Temporary Developement 
//echo '<br /><br /><hr />Doorlooptijd: '. round( (microtime(true)-$iStart)*1000 , 2) . 'm.s.';

class Events3 {
  // Singleton pattern
  private static $_oInstance = null;
  // true for development, false for producton

  public $bDebug = FALSE;

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
   * First argument shoudl be the case sensitive event name
   * followed by a optional number of extra parameters
   *
   * @return void
   */
  public function Raise() {
    // If a module/class is instantiated it is saved 
    // for the duration of the request.
    static $aInstances = array();
    $aParams = func_get_args();
    $sEvent = array_shift($aParams);
    $aModuleList = $this->GetModules( $sEvent );
    $sEventName = 'Events3'.$sEvent;
    foreach($aModuleList as $cModulePath ) {
      // Lazy loading!!!
      if( !array_key_exists($cModulePath, $aInstances)) {
        $cModuleName = basename($cModulePath);
        $cModuleFile = $cModulePath . '/' . $cModuleName . '.php';
        if(is_readable($cModuleFile)) {
          include_once $cModuleFile;
          $aInstances[$cModulePath] = new $cModuleName;
        }
      }
      $oModule = $aInstances[$cModulePath];
      call_user_func_array(array($oModule, $sEventName), $aParams);
    }
    //print_r(get_defined_vars());
    //echo '<br />';
  }

  /**
   * Events3::GetModules()
   *
   * Return module instances that implement a specified event
   *
   * @param string $sEvent
   * @return void
   */
  private function GetModules( $sEvent ) {
    $aModules = array();
    $aFullModuleList = $this->GetModuleList();
    return (array) $aFullModuleList[ $sEvent ];
  }

  /**
   * Events3::GetModuleList()
   *
   * Create a list of all available modules and the events they implement.
   * In production mode this list is fully cached for optimal performance
   *
   * So, lazy loading is only available in production mode because we
   * need to determine which modules implement our events.
   * Once we know that, we only include the modules at the moment the
   * first event is fired.
   *
   *
   *
   * @return void
   */
  private function GetModuleList(){
    // Implement static caching
    // First level cache
    static $cache = null;
    if(!is_null($cache)) {
      return $cache;
    } 
    
    // Implement file caching
    // Second level cache
    if (!$this->bDebug) {
      // Regenerate cache every minute
      $iTimeStamp = (integer) (time()/60);
      $sFileName = sys_get_temp_dir() . '/Events3EventCache.' . $iTimeStamp . '.tmp';
      if (is_readable($sFileName)) {
        $aEventList = (array) unserialize(file_get_contents($sFileName));
        if(count($aEventList)>1) {
          return $aEventList;
        }
      }
    }
    
    // Scan all the module paths recursive for all the packages
    $aList = array();
    $aLibraries = $this->GetModulePaths();
    foreach($aLibraries as $sPath) {
      $aList = array_merge($aList, $this->_getModuleListRecursive($sPath));
    }
    // Now scan all the packages for the events the implement
    $aEventList = array();
    foreach($aList as $cModulePath) {
      $cModuleName = basename($cModulePath);
      $cModuleFile = $cModulePath . '/' . $cModuleName . '.php';
      if(is_readable($cModuleFile)) {
        include_once $cModuleFile;
        $aMethods = get_class_methods( $cModuleName );
        foreach($aMethods as $cMethodName) {
          if ( strpos($cMethodName,'Events3') === 0 ) {
            $cEventName = substr($cMethodName, 7);
            $aEventList[ $cEventName ][] = $cModulePath;
          }
        }
      }
    }
    
    // Write file cache
    if (!$this->bDebug) {
       file_put_contents($sFileName, serialize($aEventList));
    }
    
    // Write static cache
    $cache = $aEventList;
    
    //print_r($aEventList);
    //echo('<br />');
    return $aEventList;
  }
  
  private function _getModuleListRecursive( $sDir ) {
    $aList = glob( $sDir . '/*', GLOB_ONLYDIR);
    foreach($aList as $sPath) {
      $aList = array_merge($aList, $this->_getModuleListRecursive($sPath));
      //$aList += $this->_getModuleListRecursive($sPath);
    }
    //print_r($aList);
    //echo('<br />');
    return $aList;
  }

  private function GetModulePaths() {
    // @todo implement addon paths
    return array( 'modules', 'lib');
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

class Events3Module  {

}