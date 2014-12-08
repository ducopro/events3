<?php

class IdfixAdmin extends Events3Module {

  /**
   * Idfix eventhandler called if the action on a
   * configuration = admin
   * 
   * @return void
   */
  public function Events3IdfixActionAdmin(&$output, $aParams) {
    if(!$this->IdfixUser->IsSuperUser()) {
        return;
    }
    
    $cSubAction = array_shift($aParams);

    // Create a sub Event for content
    $cContent = '';
    $cEventname = 'Admin' . ucfirst($cSubAction);
    $this->Idfix->Event($cEventname, $cContent, $aParams);
    $cContentWrapped = $this->RenderTemplate('AdminContent', array('content' => $cContent));

    $aTemplateVars = array(
      'title' => $this->Idfix->aConfig['title'],
      'icon' => $this->Idfix->GetIconHTML($this->Idfix->aConfig['icon']),
      'menu' => $this->GetMenu(),
      'content' => $cContentWrapped,
      );
    $output = $this->RenderTemplate('Admin', $aTemplateVars);

    $this->IdfixDebug->Debug(__method__, get_defined_vars());
  }

  public function Events3IdfixAdminMysql(&$output, $aParams) {

    // Process commands
    $cActionCommand = array_shift($aParams);
    $cActionTable = array_shift($aParams);
    $cSql = '';
    if ($cActionCommand == 'drop') {
      $cSql = 'DROP TABLE ' . $cActionTable;
    }
    if ($cSql) {
      $this->Idfix->FlashMessage($cSql);
      $this->Database->Query($cSql);
    }

    // Build the list
    $aTables = $this->Database->ShowTables();
    $this->Table->SetHeader(array(
      'Tablename',
      'Delete',
      //'Empty',
      //'Remove all traces',
      '#'));

    foreach ($aTables as $cTableName) {
      $iCount = $this->Database->CountRecords($cTableName);
      $cClass = (($iCount > 2) ? 'success' : 'warning');
      $cUrlDelete = $this->Idfix->GetUrl('', '', '', 0, 0, 'admin/mysql/drop/' . $cTableName);
      //$cClass= 'warning';

      $this->Table->SetRow(array(
        $cTableName,
        $this->GetButton('admin/mysql/drop', 'Delete', $cTableName, 'Are you sure you want to drop/delete'),
        //$this->GetButton('admin/mysql/truncate', 'Empty', $cTableName, 'Are you sure you want to truncate/empty', 'warning'),
        //$this->GetButton('admin/mysql/remove', 'Remove all', $cTableName, 'Are you sure you want to remove aoll traces from', 'danger'),
        $iCount,
        ), array('class' => $cClass));
    }

    $output = $this->Table->GetTable(array('class' => 'table table-bordered '));


  }

  private function GetButton($cAction, $cButtontext, $cTableName, $cOnclickText = '', $cClass = 'danger') {
    if ($cOnclickText) {
      $cOnclickText = "onclick=\"return confirm('{$cOnclickText} {$cTableName}?')\"";
    }
    $cUrl = $this->Idfix->GetUrl('', '', '', 0, 0, $cAction . '/' . $cTableName);
    $cRetval = "<a {$cOnclickText} class=\"btn btn-{$cClass}\" href=\"{$cUrl}\">{$cButtontext}</a>";
    return $cRetval;
  }


  private function GetMenu() {
    $cOut = '';
    $aMenu = array(
      'MySql' => $this->Idfix->GetUrl('', '', '', 0, 0, 'admin/mysql'),
      'Access' => $this->Idfix->GetUrl('', '', '', 0, 0, 'admin/access'),
      'Billing' => $this->Idfix->GetUrl('', '', '', 0, 0, 'admin/billing'),
      'History' => $this->Idfix->GetUrl('', '', '', 0, 0, 'admin/history'),
      'Activity' => $this->Idfix->GetUrl('', '', '', 0, 0, 'admin/activity'),
      );

    foreach ($aMenu as $cTitle => $cUrl) {
      $cOut .= $this->RenderTemplate('AdminMenuItem', compact('cTitle', 'cUrl'));
    }

    return $this->RenderTemplate('AdminMenu', compact('cOut'));
  }


  /**
   * Render the templates from our own template directory
   * 
   * @param mixed $cTemplateName
   * @param mixed $aVars
   * @return
   */
  public function RenderTemplate($cTemplateName, $aVars = array()) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $cTemplateFile = dirname(__file__) . "/templates/{$cTemplateName}.php";
    $cReturn = $this->Template->Render($cTemplateFile, $aVars);
    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $cReturn;
  }


}
