<?php

class TestIdfix extends Events3TestCase
{

    public function Events3Test()
    {
        /* @var $idfix Idfix*/
        $idfix = $this->load('Idfix');
        $this->assert(is_a($idfix, 'Idfix'));


        $out = $idfix->Render('testconfig', 'todo', 'field1', 0, 'list');
        $this->assert(is_string($out));
        // Ok, the eventhandling is working
        $this->assert(strpos($out, 'testconfig'));
        $this->assert( $idfix->aConfig['name'] == 'IdFixTest');
        
        // Template system
        $cOut = $idfix->RenderTemplate('UnitTest');
        $this->assert( strpos($cOut, 'Hello Idfix'));
        
        
    }

    public function Events3IdfixGetConfig()
    {
        /* @var $idfix Idfix*/
        $idfix = $this->load('Idfix');
        $cConfigName = $idfix->cConfigName;

        if ($cConfigName == 'testconfig')
        {
            $idfix->aConfig = array(
                'name' => 'IdFixTest',
                'description' => 'Configuration test for the Idfix system',
                'iconlib' => 'http://cdn.dustball.com/',
                // Table descriptions
                'tables' => array(
                    // Todo list
                    'todo' => array(
                        'name' => 'Todo List System',
                        'description' => 'Give this system a few items todo :-)',
                        'icon' => 'time.png',
                        'pager' => 25,
                        // Fieldlist for the todo system
                        'fields' => array(
                            'field1' => array(),//
                        ),
                    ),
                    // Userlist
                    'Users' => array(),
                    ),
                );
        }
    }

    public function Events3IdfixActionList(&$output)
    {
        /* @var $idfix Idfix*/
        $idfix = $this->load('Idfix');
        $cConfigName = $idfix->cConfigName;

        if ($cConfigName == 'testconfig')
        {
            $output .= "##{$cConfigName}##";
        }
    }

}
