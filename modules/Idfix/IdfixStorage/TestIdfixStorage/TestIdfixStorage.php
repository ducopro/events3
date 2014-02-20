<?php

class TestIdfixStorage extends Events3TestCase
{

    /**
     * Test the storage procedures as a post test event beacause we are depending
     * on the testcode from the idfix module.
     * We need to be sure this code runs later.
     * 
     * @return void
     */
    public function Events3PostTest()
    {
        $storage = $this->load('IdfixStorage');
        // Little bit storage that depends on this config
        $storage = $this->load('IdfixStorage');
        $this->assert($storage->GetTableSpaceName() == 'testconfig');

        $odb = $this->load('Database');
        $this->assert( count($odb->ShowTables('idfix') )==1  );
        $cTable = $storage->GetTableSpaceName();
        $this->assert( count($odb->ShowTables($cTable) )==1  );
        
        // Now set some data
        $data = array(
          'TypeID' => 10,
          'Description' => 'Testrecord',
        );
        $iNewId = $storage->SaveRecord($data);
        
        $aRow = $storage->LoadRecord($iNewId);
        $this->assert( $aRow['TypeID'] == 10);
        
        $data = array(
          'MainID' => $iNewId,
          'TypeID' => 20,
          'Description' => 'Testrecord was ' . $iNewId,
        );
        $PrevId = $storage->SaveRecord($data);

        $aRow = $storage->LoadRecord($PrevId, false); // nocache!!
        $this->assert( $aRow['TypeID'] == 20);
        

        }
}
