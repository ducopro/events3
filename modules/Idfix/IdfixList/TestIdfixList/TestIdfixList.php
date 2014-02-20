<?php

class TestIdfixList extends Events3TestCase
{

    public function Events3PostTest()
    {
        $oIdfixList = $this->load('IdfixList');
        
        $aPager = array();
        $oIdfixList->Events3IdfixActionListPager( $aPager );
        $this->assert( isset($aPager['iPageTotal'] ));
        $this->assert( $aPager['iSetStart'] <= $aPager['iSetStop']);
        
        
    }
}
