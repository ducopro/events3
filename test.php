<?php

/* 
 * Implement CLI Unit testing
 * 
 * Unit testing is fully implemented using the 
 * module loading system and event handling of the framework.
 * 
 * Unit Testing is implemented int the Unit Test module
 * 
 * THIS FILE SHOULD NOT BE PRESENT IN A PRODUCTION ENVIRONMENT!
 */

require_once 'events3.php';

/* @var $Events3 Events3*/
$Events3 = Events3::GetHandler();

$Events3->ConfigFile = dirname(__FILE) . '/config/config.test.ini';

// This is the main event in the UnitTest module
$Events3->Test();
