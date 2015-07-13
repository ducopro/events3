<?php

/**
 * 
 * 
 * C O P Y
 * 
 * 
 */

class IdfixAction extends Events3Module {

  public function Events3IdfixActionCopy(&$output) {
    // First check access!!!!
    if ($this->Idfix->Access($this->Idfix->cTableName . '_c')) {

      $record = $this->IdfixStorage->LoadRecord($this->Idfix->iObject);

      // if the default "Name" field is used, indicate that this is in
      // fact a copy
      if (isset($record['Name'])) {
        $record['Name'] .= ' ' . '(copy)';
      }

      $this->CopyRecurse($record, $record['ParentID']);
      //_asterix_runtime_copy_recurse($record, $record['ParentID'], $config_name);
    }

    // Goto the last known page of the list
    $cUrl = $this->Idfix->GetSetLastListPage($this->Idfix->cTableName);
    //$cUrl = $this->Idfix->GetUrl('', '', '', $iLastPage, $this->Idfix->iParent, 'list');
    //header('location: ' . $cUrl);
    $this->Idfix->RedirectInline($cUrl);
  }

  private function CopyRecurse($record, $parent_id) {
    $main_id = (integer)$record['MainID'];
    unset($record['MainID']);
    $record['ParentID'] = $parent_id;

    //$new_main_id = asterix_api_save($record, $config_name);
    $new_main_id = $this->IdfixStorage->SaveRecord($record);

    // Now get all child records
    //$childs = asterix_api_load_all($config_name, null, $main_id);
    $childs = $this->IdfixStorage->LoadAllRecords(null, $main_id);

    foreach ($childs as $child_id => $child_record) {
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
  public function Events3IdfixActionDelete(&$output) {
    // First check access!!!!
    if ($this->Idfix->Access($this->Idfix->cTableName . '_d')) {
      // Only delete top level object
      $this->IdfixStorage->DeleteRecord($this->Idfix->iObject);
      // Now create a url for deleting the children
      $cTaskUrl = $this->Idfix->GetUrl('', '', '', $this->Idfix->iObject, null, 'deletechildren');
      // And set is as an asynchronous client call
      $this->Idfix->GetSetClientTaskUrl($cTaskUrl);

    }

    // Goto the last known page of the list
    $cUrl = $this->Idfix->GetSetLastListPage($this->Idfix->cTableName);
    //header('location: ' . $cUrl);
    $this->Idfix->RedirectInline($cUrl);

  }
  public function Events3IdfixActionDeletechildren(&$output) {
    $this->log('Recursive deletion of children in background: start ');
    // First check access!!!!
    if ($this->Idfix->Access($this->Idfix->cTableName . '_d')) {
      $iCount = $this->DeleteRecurse($this->Idfix->iObject);
      $this->log('Recursive deletion of children in background: ' . $iCount);
    }
    $this->log('Recursive deletion of children in background: stop ');
    // This one is called as a background proces.
    // So, just exit
    exit(0);
  }

  /**
   * IdfixAction::DeleteRecurse()
   * 
   * We call this method from the rest services because we already did 
   * the access checking
   * 
   * @param integer $iParentId
   * @return void
   */
  public function DeleteRecurse($iParentId) {
    $iCount = 1;
    $aRecords = $this->IdfixStorage->LoadAllRecords(null, $iParentId);
    foreach ($aRecords as $iMainId => $aRow) {
      $iCount += $this->DeleteRecurse($iMainId);
    }

    $this->IdfixStorage->DeleteRecord($iParentId);
    return $iCount;
  }

}
