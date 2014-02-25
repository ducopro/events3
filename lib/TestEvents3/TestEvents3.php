<?php

/*
 * Test the core functionality of the events3 framework
 * 
 * As far as possible of cource.......
 * Because the unittest is also using the event dispatching system.....
 */

class TestEvents3 extends Events3TestCase {

    public function Events3Test() {
        $events3 = Events3::GetHandler();
        
        // Test the capability of changing the variable value in the event handler.
        $parameter = array();
        $events3->Raise('TestEvents3Raise', $parameter);
        $this->assert( count($parameter) > 1 );

        // Now test some extra parameters
        $value = 'HelloEvents3UnitTest';
        $parameter = array();
        $events3->Raise('TestEvents3Raise', $parameter, $value);
        //print_r($parameter);
        $this->assert( array_pop($parameter) == $value );
        
        // get a reference to the base class and test module loading
        $oModule = new Events3Module();
        $this->assert( is_object( $oModule->load('session')) );
        $this->assert( is_object( $oModule->session ) );
        
    }

    public function Events3TestEvents3Raise( &$param, $extra = 'wim' ) {
        $param[] = 'hello';
        $param[] = $extra;
        
    }
    
}
