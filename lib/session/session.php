<?php

/* 
 * Session handler
 * 
 */

class session extends Events3Module {
    
    function Events3PreRun() {
        // Initialiseer de sessie
        //echo 'Sessie geinitialiseerd'; 
        session_start();
    }
    
    function Events3SessionDestroy() {
        session_destroy();
    }
    
    function Destroy() {
        session_destroy();
    }
   
    
}

