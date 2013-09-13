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

// All functionality of the events3 framwork is in this
// handler class. Get the event handler by way of this call.
// It is a singleton pattern implementation
$Events3 = Events3::GetHandler();
// Set some properties. Note that these are the default settings
// and only displayed for demonstrating the basic configuration of the handler
$Events3->bDebug = false;  //
$Events3->Run();

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
    // Trigger the Run event
    $this->Raise('Run');
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
    $aParams = func_get_args();
    $sEvent = array_shift($aParams);
    $aModuleList = $this->GetModules( $sEvent );
    $sEventName = 'Events3'.$sEvent;
    foreach($aModuleList as $oModule ) {
      call_user_func_array(array($oModule, $sEventName), $aParams);
    }
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
    $aFullModuleList = $this->GetModuleList();

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
    $aLibraries = $this->GetModulePaths();
    foreach($aLibraries as $sPath) {

    }
  }

  private function GetModulePaths() {
    // @todo implement addon paths
    return array( 'modules/');
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
    return $self::$_oInstance;
  }
}

class Events3Module  {

}