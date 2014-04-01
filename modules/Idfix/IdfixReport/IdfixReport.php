<?php

/**
 * Reporting Engine
 * 
 * Shows aggregated information under the list taking the current filtering
 * into account.
 * 
 * FieldNajme and grouping field should be real SQL Columns, otherwise a warning
 * will be displayed.
 * 
 * Configuration Properties
 * #tables
 *      #<table_1>
 *          #fields
 *          #list
 *          #search
 *          #report
 *              #<FieldName_1>
 *                  -title=[default from field]
 *                  -description=[default from field]
 *                  -icon=[default from field]
 *                  -type=[avg,sum,count,max,min]
 *                  -distinct=[0,1]
 *                  -group=<FieldName>
 *                  -graph=[bar,pie]
 *              #<FieldName_2>
 * 
 */

class IdfixReport extends Events3Module
{

    /**
     * Events
     */
    public function Events3IdfixActionListAfter(&$output)
    {
        $output .= $this->BuildReport();
    }
    public function Events3IdfixGetConfigAfter()
    {
        $this->CreateDefaults();
    }


    /**
     * Worker functions
     */


    /**
     * Render the individual report panels and combine them in a template
     * 
     * @return
     */
    private function BuildReport()
    {
        $aReportPanels = array();
        $aReportConfig = $this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['report'];
        foreach ($aReportConfig as $cReportFieldName => $aReportFieldConfig) {
            $aReportPanels[] = $this->BuildReportPanel($cReportFieldName, $aReportFieldConfig);
        }
        return $this->Idfix->RenderTemplate('Report', compact('aReportPanels'));
    }

    private function BuildReportPanel($cFieldName, $aConfig)
    {
        // Create the SQL
        $cSql = $this->BuildReportPanelSql($cFieldName, $aConfig);
        // Retrieve the data
        $aData = $this->Database->DataQuery($cSql);
        // Is it a single data item or aggregated information?
        $cDetails = 'No Information Available';
        if (count($aData) == 1) {
            $aRow = array_shift($aData);
            $aTemplateVars['sql'] = $cSql;
            $aTemplateVars['data'] = $aRow['data']; 
            $cDetails = $this->Idfix->RenderTemplate('ReportPanelSingle', $aTemplateVars);
        }
        elseif (count($aData) > 2) {
            $aRollUp = array_pop($aData);
            $aTemplateVars = compact('aData', 'aRollUp', 'cFieldName', 'aConfig');
            $cDetails = $this->Idfix->RenderTemplate('ReportPanelMulti', $aTemplateVars);
        }

        $aTemplateVars = array(
            'title' => $aConfig['title'],
            'description' => $aConfig['description'],
            'icon' => $this->Idfix->GetIconHTML($aConfig),
            'data' => $cDetails,
            );
        return $this->Idfix->RenderTemplate('ReportPanel', $aTemplateVars);
    }

    /**
     * Create the correct SQL for retrieving the information
     * 
     * @param mixed $aConfig
     * @return
     */
    private function BuildReportPanelSql($cFieldName, $aConfig)
    {
        // First create the basics
        $cTableName = $this->IdfixStorage->GetTableSpaceName();
        $cFunction = strtoupper($aConfig['type']);
        $cDistinct = $aConfig['distinct'] ? 'DISTINCT' : '';
        $cSql = "{$cFunction}({$cDistinct} {$cFieldName}) as data FROM {$cTableName}";
        // Than check for grouping
        if ($aConfig['group']) {
            $cGroupName = $aConfig['group'];
            $cSql = "{$cGroupName} as group," . $cSql . " GROUP BY {$cGroupName} WITH ROLLUP";
        }
        return 'SELECT ' . $cSql;
    }
    /**
     * Create intelligent defaults for the report configuration
     * These checks are only done once, than the config is cached.
     * 
     * @return void
     */
    private function CreateDefaults()
    {
        // Columns form the current tablespace
        $aColumns = $this->IdfixStorage->GetTableSpaceColumns();
        // Current configuration
        $aConfig = &$this->Idfix->aConfig;
        if (isset($aConfig['tables'])) {
            // Check the table structure
            foreach ($aConfig['tables'] as $cTableName => &$aTableConfig) {
                if (isset($aTableConfig['report']) and is_array($aTableConfig['report'])) {
                    // Check the report structure
                    foreach ($aTableConfig['report'] as $cReportFieldName => &$aReportFieldConfig) {
                        // Get the original field configuration
                        $aFieldConfig = array();
                        if (isset($aTableConfig['fields'][$cReportFieldName])) {
                            $aFieldConfig = $aTableConfig['fields'][$cReportFieldName];
                        }
                        // Set some properties from the field configuration
                        $cProperty = 'title';
                        if (isset($aFieldConfig[$cProperty]) and !isset($aReportFieldConfig[$cProperty])) {
                            $aReportFieldConfig[$cProperty] = $aFieldConfig[$cProperty];
                        }
                        $cProperty = 'description';
                        if (isset($aFieldConfig[$cProperty]) and !isset($aReportFieldConfig[$cProperty])) {
                            $aReportFieldConfig[$cProperty] = $aFieldConfig[$cProperty];
                        }
                        $cProperty = 'icon';
                        if (isset($aFieldConfig[$cProperty]) and !isset($aReportFieldConfig[$cProperty])) {
                            $aReportFieldConfig[$cProperty] = $aFieldConfig[$cProperty];
                        }
                        // Set some other default properties
                        $cProperty = 'type';
                        if (!isset($aReportFieldConfig[$cProperty])) {
                            $aReportFieldConfig[$cProperty] = 'count';
                        }
                        else {
                            // Check if the type is ok
                            $cType = $aReportFieldConfig[$cProperty];
                            if (!stripos(',avg,count,sum,min,max', $cType)) {
                                $aReportFieldConfig[$cProperty] = 'count';
                            }
                        }
                        $cProperty = 'distinct';
                        if (!isset($aReportFieldConfig[$cProperty])) {
                            $aReportFieldConfig[$cProperty] = 0;
                        }
                        $cProperty = 'group';
                        if (!isset($aReportFieldConfig[$cProperty])) {
                            $aReportFieldConfig[$cProperty] = '';
                        }
                        $cProperty = 'graph';
                        if (!isset($aReportFieldConfig[$cProperty])) {
                            $aReportFieldConfig[$cProperty] = '';
                        }
                        // Is this reportfield a real SQL Column???
                        if (!isset($aColumns[$cReportFieldName])) {
                            // No??? Than delete this reportfield!!!
                            unset($aTableConfig['report'][$cReportFieldName]);
                        }
                        // Do the same check for the grouping field
                        if ($cGroupField = $aReportFieldConfig['group']) {
                            // Ok, we have set a grouping field
                            if (!isset($aColumns[$cGroupField])) {
                                // Only delete the grouping
                                $aReportFieldConfig['group'] = '';
                            }
                        }


                    }
                }
                else {
                    $aTableConfig['report'] = array();
                }
            }
        }
    }


    private function GetReportConfig()
    {
        $aTableConfig = $this->Idfix->aConfig['tables'][$this->Idfix->cTableName];
        $aReportConfig = array();
        if (isset($aTableConfig['report'])) {
            $aReportConfig = $aTableConfig['report'];
        }
        // Set some defaults from the field
        $aDefaults = array(
            'title',
            'description',
            'icon');
        foreach ($aReportConfig as $cReportFieldName => &$aReportFieldConfig) {
            // Set some intelligent defaults
            // Get the original field configuration
            $aFieldConfig = array();
            if (isset($aTableConfig['fields'][$cReportFieldName])) {
                $aFieldConfig = $aTableConfig['fields'][$cReportFieldName];
            }
            // Set the default properties
            foreach ($aDefaults as $cProperty) {
                // We did not set this property???
                if (!isset($aReportFieldConfig[$cProperty])) {
                    // First create a default
                    $aReportFieldConfig[$cProperty] = ''; // And we have it in the default??
                    if (isset($aFieldConfig[$cProperty])) {
                        // Than set it in the report
                        $aReportFieldConfig[$cProperty] = $aFieldConfig[$cProperty];
                    }
                }
            }
        }


    }
}
