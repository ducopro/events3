<?php

/**
 * Functionality for rendering edity forms and storing POST information
 * 
 */
class IdfixEdit extends Events3Module
{
    // Check if it is the correct form we are validating
    private $cCheckSum = null;
    // Should we be rendering the form or should we be validating the form??
    private $bValidationMode = false;
    // Are the any errors detected  in Validationmode???
    private $bErrorsDetected = false;
    // Save all the values in this array
    private $aDataRow = array();

    public function Events3IdfixActionEdit(&$output)
    {
        $this->IdfixDebug->Profiler( __METHOD__, 'start');
        // Id of the record we need to edit
        $iMainID = $this->Idfix->iObject;
        // Fullly loaded record from disk
        $this->aDataRow = $this->IdfixStorage->LoadRecord($iMainID);
        // And get to the table configuration
        $cConfigName = $this->Idfix->cConfigName;
        $cTableName = $this->Idfix->cTableName;
        $aTableConfig = $this->Idfix->aConfig['tables'][$cTableName];
        // Trigger procedure and search and replace field values
        $aTableConfig = $this->Idfix->PostprocesConfig($aTableConfig, $this->aDataRow);
        // Create key to check if we are validating the correct form
        $this->cCheckSum = md5($cConfigName . $cTableName . $iMainID);
        // If this checksum is present in the POST infomation it means
        // we should be validating and saving the values.
        $this->bValidationMode = (isset($_POST['_checksum']) and $_POST['_checksum'] == $this->cCheckSum);
        // Get off of the HTML for the form and while doing that also do some validation
        $cHtmlInputForm = $this->GetHtmlForForm($aTableConfig);

        $this->IdfixDebug->Debug('Configuratie', array($cConfigName, $cTableName, $iMainID));
        $this->IdfixDebug->Debug('table', $cTableName);

        // By now we know if there were no errors and we can save the values
        if ($this->bValidationMode and !$this->bErrorsDetected)
        {
            // Save
            $this->IdfixStorage->SaveRecord($this->aDataRow);
            // Than return to the list
            $cUrl = $this->Idfix->GetUrl($cConfigName,$cTableName,'',1,null,'list');
            //header('location: ' . $cUrl);
        }

        // Now wrap the raw html in it's form tag and add some hidden fields
        $aTemplate = array(
            'iMainID' => $iMainID,
            'cInput' => $cHtmlInputForm,
            'cHidden' => $this->GetHtmlHiddenFields($aTableConfig),
            'cTitle' => $aTableConfig['title'],
            'cDescription' => $aTableConfig['description'],
            'cIcon' => $this->Idfix->GetIconHTML($aTableConfig['icon']),
            'cPostUrl' => $this->Idfix->GetUrl($cConfigName,$cTableName,'',$iMainID,null,'edit'),
            );

        $output = $this->Idfix->RenderTemplate('EditForm', $aTemplate);
        $this->IdfixDebug->Profiler( __METHOD__, 'stop');
    }

    /**
     * Get the full HTML representation of the form.
     * Take into account that we may need to group the fields 
     * 
     * @param array $aTableConfig
     * @param array $aDataRowFromDisk
     * @return string HTML
     */
    private function GetHtmlForForm($aTableConfig)
    {
        $cReturn = '';
        if (isset($aTableConfig['groups']) and is_array($aTableConfig['groups']))
        {
            foreach ($aTableConfig['groups'] as $cGroupId => $aGroupConfig)
            {
                // Build the template variables
                $aTemplate = array(
                    'cElements' => $this->GetHtmlForInputElements($aTableConfig['fields'], $cGroupId),
                    'cId' => $this->Idfix->ValidIdentifier($cGroupId),
                    'cTitle' => $aGroupConfig['title'],
                    'cDescription' => $aGroupConfig['description'],
                    'cIcon' => $this->Idfix->GetIconHTML($aGroupConfig['icon']),
                    );
                $cReturn .= $this->Idfix->RenderTemplate('EditFormGroup', $aTemplate);
            }
        } else
        {
            $cReturn .= $this->GetHtmlForInputElements($aTableConfig['fields']);
        }
        return $cReturn;
    }

    /**
     * Create HTML representation for an input element
     * 
     * @param array $aFieldList Field configuration from the tableconfig
     * @param array $aDataRowFromDisk Loaded values from the datatable
     * @param string $cGroup Name of the group if we need te return the HTML only for this group
     * @return string HTML
     */
    private function GetHtmlForInputElements($aFieldList, $cGroup = '')
    {
        $this->IdfixDebug->Profiler(__METHOD__, 'start');
        $cReturn = '';
        foreach ($aFieldList as $cFieldName => $aFieldConfig)
        {
            $cInput = '';
            // Skip virtual fields
            if ($aFieldConfig['type'] == 'virtual')
            {
                continue;
            }

            // If a group is set, and there is no match, we skip this field
            if (isset($aFieldConfig['group']) and $aFieldConfig['group'] != $cGroup)
            {
                continue;
            }
            // If no group is set, and we need to get items for a group, skip it also
            if (!isset($aFieldConfig['group']) and $cGroup)
            {
                continue;
            }
            // Check if we have the correct rights to show or edit the field
            $bAllowEdit = $this->Idfix->FieldAccess($this->Idfix->cConfigName, $aFieldConfig['_tablename'], $cFieldName, 'edit');
            $bAllowView = true;
            if (!$bAllowEdit)
            {
                $bAllowView = $this->Idfix->FieldAccess($this->Idfix->cConfigName, $aFieldConfig['_tablename'], $cFieldName, 'view');
            }
            // No need to go on if we do not have at least VIEW rights
            if (!$bAllowView)
            {
                continue;
            }

            // Optional value from the table
            $xRawValue = (isset($this->aDataRow[$cFieldName]) ? $this->aDataRow[$cFieldName] : null);
            // Optional value from POST
            $xRawPostValue = (isset($_POST[$cFieldName]) ? $_POST[$cFieldName] : null);
            $aFieldConfig['__RawValue'] = $xRawValue;
            $aFieldConfig['__RawPostValue'] = $xRawPostValue;

            // Depending on the the rights we need to display the edit element or the view element
            if ($bAllowEdit)
            {
                $this->Idfix->Event('EditField', $aFieldConfig);
                // If there is a value to save, we need to keep it....
                $this->aDataRow[$cFieldName] = $aFieldConfig['__SaveValue'];
            } elseif ($bAllowView)
            {
                $this->Idfix->Event('DisplayField', $aFieldConfig);
            }

            // Last but not least check if there were any errors detected
            if ($this->bValidationMode and !$this->bErrorsDetected)
            {
                $this->bErrorsDetected = (boolean)$aFieldConfig['__ValidationError'];
            }

            $cInput = $aFieldConfig['__DisplayValue'];
            $cReturn .= $cInput;
        }
        $this->IdfixDebug->Profiler( __METHOD__, 'stop');
        return $cReturn;
    }

    private function GetHtmlHiddenFields($aTableConfig)
    {
        $cReturn = '';
        $cReturn .= $this->GetHiddenField('_checksum', $this->cCheckSum);
        $cReturn .= $this->GetHiddenField('_tablename', $aTableConfig['_name']);
        $cReturn .= $this->GetHiddenField('_configname', $this->Idfix->cConfigName);
        //$cReturn .= $this->GetHiddenField('_return', $_SERVER['HTTP_REFERER']);
        $cReturn .= "<button class=\"btn btn-primary\" type=\"submit\" value=\"Save\" name=\"_idfix_save_button\">Save</button>&nbsp;";
        $cReturn .= "<button class=\"btn btn-success\" type=\"submit\" value=\"Cancel\" name=\"_idfix_cancel_button\">Cancel</button>";
        return $cReturn;
    }
    private function GetHiddenField($cName, $cValue)
    {
        $cName = $this->Idfix->ValidIdentifier($cName);
        $cValue = $this->Idfix->ValidIdentifier($cValue);
        return "<input type=\"hidden\" name=\"{$cName}\" value=\"{$cValue}\">";
    }
}
