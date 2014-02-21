<?php

class IdfixList extends Events3Module
{

    private $oIdfix, $oIdfixStorage, $oDatabase;

    public function Events3IdfixActionList(&$output)
    {
        /* @var $this->oIdfix Idfix*/
        $this->oIdfix = $this->load('Idfix');
        /* @var $this->oIdfixStorage IdfixStorage*/
        $this->oIdfixStorage = $this->load('IdfixStorage');
        /* @var $this->oIdfixDatabase IdfixDatabase*/
        $this->oDatabase = $this->load('Database');

        $aTemplateVars = array();

        // Get the title
        $cHook = 'ActionListTitle';
        $aData = array();
        $this->oIdfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->oIdfix->RenderTemplate($cHook, $aData);
        // Get the Breadcrumb trail
        $cHook = 'ActionListBreadcrumb';
        $aData = array();
        $this->oIdfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->oIdfix->RenderTemplate($cHook, $aData);
        // Get the buttonbar
        $cHook = 'ActionListButtonbar';
        $aData = array();
        $this->oIdfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->oIdfix->RenderTemplate($cHook, $aData);
        // Get the grid
        $cHook = 'ActionListMain';
        $aData = array();
        $this->oIdfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->oIdfix->RenderTemplate($cHook, $aData);
        // Get the pager
        $cHook = 'ActionListPager';
        $aData = array();
        $this->oIdfix->Event($cHook, $aData);
        $aTemplateVars[$cHook] = $this->oIdfix->RenderTemplate($cHook, $aData);

        // Put them in the template
        $output = $this->oIdfix->RenderTemplate('ActionList', $aTemplateVars);
    }


    public function Events3IdfixActionListTitle(&$aTitle)
    {
        $aTitle['cTitle'] = $this->oIdfix->CleanOutputString($this->oIdfix->aConfig['title']);
        $aTitle['cDescription'] = $this->oIdfix->CleanOutputString($this->oIdfix->aConfig['description']);
    }

    public function Events3IdfixActionListBreadcrumb(&$aData)
    {
        $aData['aBreadcrumb'] = array( //
            'Home' => $this->oIdfix->GetUrl($this->oIdfix->cConfigName), //
            'Level 1' => $this->oIdfix->GetUrl($this->oIdfix->cConfigName), //
            'Level 2' => $this->oIdfix->GetUrl($this->oIdfix->cConfigName), //
            'Level 3' => $this->oIdfix->GetUrl($this->oIdfix->cConfigName), //
            'Level 4' => $this->oIdfix->GetUrl($this->oIdfix->cConfigName), //
            );
    }
    public function Events3IdfixActionListMain(&$aData)
    {
        $aData['aHead'] = array('Column #1','Column #2', 'Column #3', 'Column #4', 'Column #5',  );
        $aData['aBody'] = array(
           array('Data #1', 'Data #2', 'Data #3', 'Data #4', 'Data #5', ),//
           array('Data #1', 'Data #2', 'Data #3', 'Data #4', 'Data #5', ),//
           array('Data #1', 'Data #2', 'Data #3', 'Data #4', 'Data #5', ),//
           array('Data #1', 'Data #2', 'Data #3', 'Data #4', 'Data #5', ),//
           array('Data #1', 'Data #2', 'Data #3', 'Data #4', 'Data #5', ),//
           array('Data #1', 'Data #2', 'Data #3', 'Data #4', 'Data #5', ),//
        );

    }

    public function Events3IdfixActionListPager(&$aPager)
    {
        // What is the total number of records???
        $cTableSpace = $this->oIdfixStorage->GetTableSpaceName();
        $iRecordsTotal = $this->oDatabase->CountRecords($cTableSpace);

        // What is the number of records on the page
        $aConfig = $this->oIdfix->aConfig;
        $cTable = $this->oIdfix->cTableName;
        $aTableConfig = $aConfig['tables'][$cTable];
        $iRecordsByPage = (integer)$aTableConfig['pager'];

        // What is the total number of pages
        $iPages = 1;
        if ($iRecordsTotal > $iRecordsByPage and $iRecordsTotal and $iRecordsByPage)
        {
            $iPages = ceil($iRecordsTotal / $iRecordsByPage);
        }

        // What is the current page?
        $iPageCurrent = $this->oIdfix->iObject;
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
