<?php

/**
 * Unit test for the Database Module
 */
class TestDatabase extends Events3TestCase
{

    public function Events3Test()
    {
        $db = $this->load('Database');
        $this->assert( is_a( $db, 'Database'));
    }
}