<?php

class TestIdfix extends Events3TestCase
{

    public function Events3Test()
    {
        /* @var $idfix Idfix*/
        $idfix = $this->load('Idfix');
        $this->assert(is_a($idfix, 'Idfix'));


        $out = $idfix->Render('testconfig', 'table1', 'field1', 0, 'list');
        $this->assert(is_string($out));
        // Ok, the eventhandling is working
        $this->assert(strpos($out, 'testconfig'));
    }

    public function Events3IdfixGetConfig($config, $cConfigName)
    {

        if ($cConfigName == 'testconfig')
        {
            $config = array(
                'description' => 'Configuration test for the Idfix system',
                'iconlib' => 'http://cdn.dustball.com/',
                // Table descriptions
                'tables' => array(
                    // Todo list
                    'Todo' => array(
                        'name' => 'Todo List System',
                        'description' => 'Give this system a few items todo :-)',
                        'icon' => 'time.png',
                        // Fieldlist for the todo system
                        'fields' => array(
                            //
                        ),
                    ),
                    // Userlist
                    'Users' => array(),
                    ),
                );
        }
    }

    public function Events3IdfixActionList(&$output, $config, $configname, $tablename, $fieldname, $object)
    {
        if ($configname == 'testconfig')
        {
            $output .= "##{$configname}##{$tablename}##$fieldname";
        }
    }

}
