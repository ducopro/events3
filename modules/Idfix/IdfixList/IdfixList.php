<?php

class IdfixList extends Events3Module
{
    
    
    public function Events3IdfixActionList(&$output)
    {
        /* @var $idfix Idfix*/
        $idfix = $this->load('Idfix');

        $aTemplateVars = array();

        // Get the title
        $cHook = 'ActionListTitle';
        $aData = array();
        $idfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $idfix->RenderTemplate($cHook, $aData);
        // Get the Breadcrumb trail
        $cHook = 'ActionListBreadcrumb';
        $aData = array();
        $idfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $idfix->RenderTemplate($cHook, $aData);
        // Get the buttonbar
        $cHook = 'ActionListButtonbar';
        $aData = array();
        $idfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $idfix->RenderTemplate($cHook, $aData);
        // Get the grid
        $cHook = 'ActionListMain';
        $aData = array();
        $idfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $idfix->RenderTemplate($cHook, $aData);
        // Get the pager
        $cHook = 'ActionListPager';
        $aData = array();
        $idfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $idfix->RenderTemplate($cHook, $aData);

        // Put them in the template
        $output = $idfix->RenderTemplate('ActionList', $aTemplateVars);
    }

    public function Events3IdfixActionListPager(&$aPager)
    {
        // What is the total number of records???
        $oIdfix = $this->load('Idfix');
        $oIdfixStorage = $this->load('IdfixStorage');
        $cTableSpace = $oIdfixStorage->GetTableSpaceName();
        $oDb = $this->load('Database');
        $iRecordsTotal = $oDb->CountRecords($cTableSpace);
        // What is the number of records on the page
        $aConfig = $oIdfix->aConfig;
        $cTable = $oIdfix->cTableName;
        $aTableConfig = $aConfig['tables'][$cTable];
        $iRecordsByPage = $aTableConfig['pager'];
        // What is the total number of pages
        $iPages = 1;
        if ($iRecordsTotal > $iRecordsByPage AND $iRecordsTotal AND $iRecordsByPage)
        {
            $iPages = ceil($iRecordsTotal / $iRecordsByPage);
        }
        // What is the current page?
        $iPageCurrent = $oIdfix->iObject;
        // What is the next page
        $iPageNext = min($iPageCurrent + 1, $iPages);
        // What is the previous page
        $iPagePrevious = max($iPageCurrent - 1, 1);

        // Try to create a set of 5 pages before and after the current page
        $iSetSize = 5;
        $iStartSet = max(1, $iPageCurrent - $iSetSize);
        $iStopSet = min($iPages, $iPageCurrent + $iSetSize);

        // Put the information in the pager-array for rendering
        $aPager['iRecordsTotal'] = $iRecordsTotal;
        $aPager['iRecordsPage'] = $iRecordsByPage;
        $aPager['iPageTotal'] = $iPages;
        $aPager['iPageCurrent'] = $iPageCurrent;
        $aPager['iPageNext'] = $iPageNext;
        $aPager['iPagePrev'] = $iPagePrevious;
        $aPager['iSetStart'] = $iStartSet;
        $aPager['iSetStop'] = $iStopSet;

    }
}
