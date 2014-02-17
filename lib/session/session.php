<?php

/* 
 * Session handler
 * 
 */

class session extends Events3Module {
    
    function Events3PreRun() {
        // Initialise session
        session_start();
    }
    
    function Destroy() {
        session_destroy();
    }

}

