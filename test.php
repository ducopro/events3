<?php

/*
* Implement CLI Unit testing
* 
* Unit testing is fully implemented using the 
* module loading system and event handling of the framework.
* 
* Unit Testing is implemented in the Unit Test module
* 
* THIS FILE SHOULD NOT BE PRESENT IN A PRODUCTION ENVIRONMENT!
*/

error_reporting(E_ALL);
function Events3Shutdown()
{
    $aErrors = error_get_last();
    if (is_array($aErrors))
    {
        echo "<h2>PHP ERROR</h2>-- Error: {$aErrors['type']}\n<br />-- Message: {$aErrors['message']}\n<br />-- File:  {$aErrors['file']}\n<br />-- Line:  {$aErrors['line']}\n<br />";
    }
}
register_shutdown_function('Events3Shutdown');

require_once 'events3.php';

/* @var $Events3 Events3*/
$Events3 = Events3::GetHandler(true); // Run in UnitTestMode

$Events3->ConfigFile = dirname(__file__) . '/config/config.test.ini';
// Set some properties. Note that these are the default settings
// and only displayed for demonstrating the basic configuration of the handler
$Events3->bDebug = true;
$Events3->bEventFileCache = true;

// This is the main event in the UnitTest module
$Events3->Test();
