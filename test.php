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

require_once 'events3.php';
register_shutdown_function('Events3Shutdown');

/* @var $Events3 Events3*/
$Events3 = Events3::GetHandler(true); // Run in UnitTestMode

$Events3->ConfigFile = dirname(__file__) . '/config/config.test.ini';
// Set some properties. Note that these are the default settings
// and only displayed for demonstrating the basic configuration of the handler
$Events3->bDebug = true;


// This is the main event in the UnitTest module
$Events3->Test();