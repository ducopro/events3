<?php

class IdfixList extends Events3Module
{

    public function Events3IdfixActionList(&$output, $config, $configname, $tablename, $fieldname, $object)
    {
        $ev3 = Events3::GetHandler();
        $aTemplateVars = array();

        // Get the title
        $title = '';
        $ev3->Raise('IdfixActionListTitle', $title, $config, $configname, $tablename);
        $aTemplateVars['title'] = $title;

        // Get the breadcrumb
        $cBreadCrumb = '';
        $ev3->Raise('IdfixActionListBreadcrumb', $cBreadCrumb, $config, $configname, $tablename);
        $aTemplateVars['breadcrumb'] = $$cBreadCrumb;

        // Get the buttonbar
        // Get the main list
        // Get the pager

        // Put them in the template
        $template = '';
        // And add it to the output
        $ouput .= $template;
    }
}
