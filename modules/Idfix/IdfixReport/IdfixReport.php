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
 *              #identifier_1
 *                  -field=<FieldName>
 *                  -field_expr=<Valid MySql Expression>
 *                  -title=[default from field]
 *                  -description=[default from field]
 *                  -icon=[default from field]
 *                  -type=[avg,sum,count,max,min]
 *                  -distinct=[0,1]
 *                  -group=<FieldName>
 *                  -group_expr=<Valid MySql Expression>
 *                  -graph=[bar,pie]
 *              #identifier_2
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
        foreach ($aReportConfig as $cReportIdentifier => $aReportFieldConfig) {
            $aReportPanels[] = $this->BuildReportPanel($aReportFieldConfig);
        }
        return $this->Idfix->RenderTemplate('Report', compact('aReportPanels'));
    }

    private function BuildReportPanel($aConfig)
    {
        // Create the SQL
        $cSql = $this->BuildReportPanelSql($aConfig);
        $this->Idfix->FlashMessage($cSql);
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
        elseif (count($aData) > 1) {
            $aRollUp = array_pop($aData);
            $aTemplateVars = compact('aData', 'aRollUp', 'aConfig');
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
    private function BuildReportPanelSql($aConfig)
    {
        // First create the basics
        $cTableName = $this->IdfixStorage->GetTableSpaceName();
        $cFunction = strtoupper($aConfig['type']);
        $cDistinct = $aConfig['distinct'] ? 'DISTINCT' : '';

        // Do we have a field or expression???
        // Expressions get precedence
        $cFieldName = $aConfig['field_expr'];
        if (!$cFieldName) {
            $cFieldName = $aConfig['field'];
        }
        // Same excersise for the group
        $cGroupName = $aConfig['group_expr'];
        if (!$cGroupName) {
            $cGroupName = $aConfig['group'];
        }

        // Build basic SQL statement. In this stadium it could happen that we do not have
        // a valid fieldname of expression. Well, that's a shame, but it just means we do not
        // see any data. It is to the developer to solve the problem
        $cSql = "{$cFunction}({$cDistinct} {$cFieldName}) as data FROM {$cTableName}";

        // Than check for grouping
        if ($cGroupName) {
            $cSql = "{$cGroupName} as separate," . $cSql . " GROUP BY {$cGroupName} WITH ROLLUP";
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
                    foreach ($aTableConfig['report'] as $cReportIdentifier => &$aReportFieldConfig) {
                        // Set the correct fieldname
                        $cReportFieldName = '';
                        if (isset($aReportFieldConfig['field'])) {
                            $cReportFieldName = $aReportFieldConfig['field'];
                        }
                        else {
                            $aReportFieldConfig['field'] = '';
                        }

                        // Get the original field configuration
                        $aFieldConfig = array();
                        if (isset($aTableConfig['fields'][$cReportFieldName])) {
                            $aFieldConfig = $aTableConfig['fields'][$cReportFieldName];
                        }

                        // Set some properties from the field configuration
                        $cProperty = 'title';
                        if (!isset($aReportFieldConfig[$cProperty])) {
                            if (isset($aFieldConfig[$cProperty])) {
                                $aReportFieldConfig[$cProperty] = $aFieldConfig[$cProperty];
                            }
                            else {
                                $aReportFieldConfig[$cProperty] = $cReportIdentifier;
                            }
                        }


                        $cProperty = 'description';
                        if (!isset($aReportFieldConfig[$cProperty])) {
                            if (isset($aFieldConfig[$cProperty])) {
                                $aReportFieldConfig[$cProperty] = $aFieldConfig[$cProperty];
                            }
                            else {
                                $aReportFieldConfig[$cProperty] = '';
                            }
                        }

                        $cProperty = 'icon';
                        if (!isset($aReportFieldConfig[$cProperty])) {
                            if (isset($aFieldConfig[$cProperty])) {
                                $aReportFieldConfig[$cProperty] = $aFieldConfig[$cProperty];
                            }
                            else {
                                $aReportFieldConfig[$cProperty] = 'dashboard';
                            }
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
                        $cProperty = 'field_expr';
                        if (!isset($aReportFieldConfig[$cProperty])) {
                            $aReportFieldConfig[$cProperty] = '';
                        }
                        $cProperty = 'group_expr';
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


}
