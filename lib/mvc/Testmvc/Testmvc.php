<?php

/*
* To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/

class Testmvc extends Events3TestCase
{

    public $bHookWasCalled = false;

    public function Events3Test()
    {
        /* @var $Events3 Events3 */
        $Events3 = Events3::GetHandler();
        $this->assert(is_object($Events3), 'Events3 Framework object kan niet worden geladen.');

        /* @var  $oMvc mvc */
        $oMvc = $Events3->LoadModule('mvc');
        $this->assert(is_object($oMvc), 'MVC module cannot be loaded.');
    }
}
