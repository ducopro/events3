<?php

class TestIdfixOtap extends Events3TestCase {
    
    public function Events3Test() {
        $this->IdfixOtap->SetupDirectoryStructure();
        $this->assert( is_dir( $this->ev3->PublicPath . '/otap') );
    }
}