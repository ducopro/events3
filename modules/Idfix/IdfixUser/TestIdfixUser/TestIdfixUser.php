<?php


class TestIdfixUser extends Events3TestCase{
    
    public function Events3Test() {
        $this->assert( is_object($this->IdfixUser));
    }
}