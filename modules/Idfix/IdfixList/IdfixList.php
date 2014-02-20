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
}
