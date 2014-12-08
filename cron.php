<?php

/**
 * Events3 Cron
 */

require_once 'events3.php';

// All functionality of the events3 framework is in this
// handler class. Get the event handler by way of this call.
// It is a singleton pattern implementation
$Events3 = Events3::GetHandler();
$Events3->debug = true;

$Events3->Raise('PreRun');

// Get the full command without the first slash
$cCommand = substr(parse_url(urldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH), 1);
// Split parts
$aParts = explode('/', $cCommand);
// All parts first char uppercase
$aParts = array_map('ucfirst', $aParts);
// .. and implode to hookname, add no extra characters
$cEventName = implode('', $aParts);

// Run the cron hooks
$Events3->Raise($cEventName);

