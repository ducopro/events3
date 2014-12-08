<?php

class IdfixFieldsInputVirtual extends IdfixFieldsInput {

  public function GetDisplay() {
    $cChildTableName = substr($this->aData['_name'], 1);
    $iParentID = $this->aData['__RawValue'];
    $bIsValidChildTable = isset($this->Idfix->aConfig['tables'][$cChildTableName]);
    // Get the default value we need to display in the button
    $cValue = $this->aData['title'];
    if (isset($this->aData['value']) and $this->aData['value']) {
      $cValue = $this->aData['value'];
    }

    // Do we need an optional icon???
    if (isset($this->aData['icon']) and $this->aData['icon']) {
      $cIconHtml = $this->Idfix->GetIconHTML($this->aData['icon']);
      if ($cValue) {
        $cValue = $cIconHtml . '&nbsp' . $cValue;
      }
      else {
        $cValue = $cIconHtml;
      }
    }

    // Create the href to the default view
    if ($bIsValidChildTable) {
      $aViewInfo = $this->Idfix->GetListViews($cChildTableName);
      $cAction = $aViewInfo['default']['action'];
      $this->aData['href'] = $this->Idfix->GetUrl('', $cChildTableName, '', 0, $iParentID, $cAction);
    }

    // Do we need to show the childcounter?
    if (isset($this->aData['counter']) and $this->aData['counter']) {
      //$this->IdfixDebug->Debug(__METHOD__, $this->aData);
      $iTypeID = $this->Idfix->aConfig['tables'][$cChildTableName]['id'];
      // Very efficient because the list is preloaded!
      // See: IdfixList::PreloadChildCounters
      $iCount = $this->IdfixStorage->GetChildCount($iTypeID, $iParentID);
      // Create the badge .... does not look good :-(
      if ($iCount) {
        //$cValue .= "&nbsp<span class=\"badge\">#{$iCount}</span>";
        $cValue .= "&nbsp(#{$iCount})";
      }
    }

    // Do we need confirmation??
    if (isset($this->aData['confirm']) and $this->aData['confirm']) {
      $cConfirm = $this->aData['confirm'];
      $this->aData['onclick'] = "return confirm('{$cConfirm}')";
    }
    // Get all the attributes
    $cAttr = $this->GetAttributes($this->aData);


    $cReturn = "<a {$cAttr}>{$cValue}</a>";

    $this->aData['__DisplayValue'] = $cReturn;
  }


}
