<?php

/**
 * Unit test for the Database Module
 */
class TestDatabase extends Events3TestCase
{

    public function Events3Test()
    {
        // Load and test our parent module. Is it available??
        $db = $this->load('Database');
        $this->assert(is_a($db, 'Database'));

       
        $db->Query('DROP TABLE IF EXISTS TEST, TEST2 ');

        $start = $db->CountTables();
        
        $this->assert($db->Query('CREATE TABLE IF NOT EXISTS TEST (iid INT, cText VARCHAR(254) )') == 0);
        $this->assert($db->CountTables() == $start+1);

        $this->assert($db->Query('CREATE TABLE IF NOT EXISTS TEST2 (iid INT, cText VARCHAR(254) )') == 0);
        $this->assert($db->CountTables() == $start+2);

        $this->assert($db->CountColumns('TEST') == 2);

        $this->assert($db->CountRecords('TEST') == 0);

        $this->assert(count($db->ShowTables()) == $start+2);
        $this->assert(count($db->ShowTables('TEST')) == 1);
        $this->assert(count($db->ShowTables('UNKNOWN')) == 0);

        $db->Query('INSERT INTO TEST (iid,cText) VALUES ("888","Hello World of 888")');
        $this->assert($db->CountRecords('TEST') == 1);

        $data = array(
            'iid' => '999',
            'cText' => 'Hello World of 999',
            );
        $db->Insert('TEST', $data);
        $this->assert($db->CountRecords('TEST') == 2);
        $db->Insert('TEST', $data);
        $this->assert($db->CountRecords('TEST') == 3);

        // Count the number of times we have 999
        $cSql = 'SELECT COUNT(*) FROM TEST WHERE iid=999';
        $this->assert($db->DataQuerySingleValue($cSql) == 2);

        $data = array(
            'iid' => '777',
            'cText' => 'Hello World of 999',
            );
        $db->Update('TEST', $data, 'iid', 999);
        $cSql = 'SELECT COUNT(*) FROM TEST WHERE iid=999';
        $this->assert($db->DataQuerySingleValue($cSql) == 0);
        $cSql = 'SELECT COUNT(*) FROM TEST WHERE iid=777';
        $this->assert($db->DataQuerySingleValue($cSql) == 2);

    }
}
