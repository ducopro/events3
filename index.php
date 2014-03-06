<?php

/**
 * Events3 Framework entryfile
 */

// Temporary Developement
$iStart = microtime(true);

require_once 'events3.php';


// All functionality of the events3 framwork is in this
// handler class. Get the event handler by way of this call.
// It is a singleton pattern implementation
$Events3 = Events3::GetHandler();
// Set some properties. Note that these are the default settings
// and only displayed for demonstrating the basic configuration of the handler
$Events3->bDebug = true;
$Events3->bEventFileCache = true;

$Events3->Run();

// Temporary Developement 

echo '<div class="container">Page build in: ' . round((microtime(true) - $iStart) * 1000, 2) . ' m.s.</div>';