<?php

class TestConfig extends Events3TestCase {

    function Events3Test() {
        // Automatic ini-file selection
        $cFile = $this->ev3->GAE_GetIniFile();
        
        $this->assert( is_readable($cFile));
        $this->assert( is_writable($cFile));

        /* @var $oConfig Config */
        //$oConfig = $this->load('Config');
        //$oConfig->Events3PreRun();

    }

    function Events3ConfigInit( &$aConfig ) {
        $key = '_My_Secret_UNIT_Test_Key_';

        $this->assert( array_key_exists( $key, $aConfig ) );

        $this->assert( is_array($aConfig));
        $aConfig[ $key ] = 'TestConfig';



}

}