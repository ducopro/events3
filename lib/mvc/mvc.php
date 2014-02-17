<?php

class mvc extends Events3Module {

    function Events3Run() {
        
        $hook = (string) @$_GET['event'];
        if (!$hook) {
          $hook = 'index';  
        } 
        
        $hook = 'mvc' . $hook;            
        $Events3 = Events3::GetHandler();
        $Events3->Raise($hook);

    }


}
