<?php

/**
 * 
 * 
 * C O P Y
 * 
 * 
 */

class IdfixAction extends Events3Module
{

    public function Events3IdfixActionCopy(&$output)
    {
        $record = $this->IdfixStorage->LoadRecord($this->Idfix->iObject);
        //$record = asterix_api_load($main_id, $config_name);

        // if the default "Name" field is used, indicate that this is in
        // fact a copy
        if (isset($record['Name']))
        {
            $record['Name'] .= ' ' . '(copy)';
        }

        $this->CopyRecurse($record, $record['ParentID']);
        //_asterix_runtime_copy_recurse($record, $record['ParentID'], $config_name);

        // Goto the last known page of the list
        $cUrl = $this->Idfix->GetSetLastListPage($this->Idfix->cTableName);
        //$cUrl = $this->Idfix->GetUrl('', '', '', $iLastPage, $this->Idfix->iParent, 'list');
        header('location: ' . $cUrl);
    }

    private function CopyRecurse($record, $parent_id)
    {
        $main_id = (integer)$record['MainID'];
        unset($record['MainID']);
        $record['ParentID'] = $parent_id;

        //$new_main_id = asterix_api_save($record, $config_name);
        $new_main_id = $this->IdfixStorage->SaveRecord($record);

        // Now get all child records
        //$childs = asterix_api_load_all($config_name, null, $main_id);
        $childs = $this->IdfixStorage->LoadAllRecords(null, $main_id);

        foreach ($childs as $child_id => $child_record)
        {
            // Don't process our base record and the copy of it!!!
            //_asterix_runtime_copy_recurse($child_record, $new_main_id, $config_name);
            $this->CopyRecurse($child_record, $new_main_id);
        }

    }


    /**
     * 
     * 
     * D E L E T E
     * 
     * 
     */
    public function Events3IdfixActionDelete(&$output)
    {
        $this->DeleteRecurse($this->Idfix->iObject);
        // Goto the last known page of the list
        $cUrl = $this->Idfix->GetSetLastListPage($this->Idfix->cTableName);
        header('location: ' . $cUrl);

    }

    private function DeleteRecurse($iParentId)
    {
        $aRecords = $this->IdfixStorage->LoadAllRecords(null, $iParentId);
        foreach($aRecords as $iMainId => $aRow) {
            $this->DeleteRecurse( $iMainId);
        }
 
        $this->IdfixStorage->DeleteRecord( $iParentId);
    }

}