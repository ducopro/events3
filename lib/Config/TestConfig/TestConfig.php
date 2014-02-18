<?php

class TestConfig extends Events3TestCase {

    function Events3Test() {
        /* @var $events3 Events3 */
        $events3 = Events3::GetHandler();
        $cFile = $events3->ConfigFile;
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