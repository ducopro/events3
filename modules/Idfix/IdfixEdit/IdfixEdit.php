<?php

/**
 * Functionality for rendering edity forms and storing POST information
 * 
 */
class IdfixEdit extends Events3Module
{
    private $cCheckSum;

    public function Events3IdfixActionEdit(&$output)
    {
        // Id of the record we need to edit
        $iMainID = $this->Idfix->iObject;
        // Fullly loaded record from disk
        $aDataRowFromDisk = $this->IdfixStorage->LoadRecord($iMainID);
        // And get to the table configuration
        $cConfigName = $this->Idfix->cConfigName;
        $cTableName = $this->Idfix->cTableName;
        $aTableConfig = $this->Idfix->aConfig['tables'][$cTableName];
        // Create key to check if we are validating the correct form
        $this->cCheckSum = md5($cConfigName . $cTableName . $iMainID);

        $cHtmlInputForm = $this->GetHtmlForForm($aTableConfig, $aDataRowFromDisk);
        $cExtraHtml = $this->GetHtmlHiddenFields( $aTableConfig, $aDataRowFromDisk );

        // Now wrap the raw html in it's form tag and add some hidden fields
        $aTemplate = array(
            'iMainID' => $iMainID,
            'cInput' => $cHtmlInputForm,
            'cHidden' => $cExtraHtml,
            'cTitle' =>$aTableConfig['title'],
            'cDescription' =>$aTableConfig['description'],
            'cIcon' => $this->Idfix->GetIconHTML( $aTableConfig['icon'] ),
            );

        $output = $this->Idfix->RenderTemplate('EditForm', $aTemplate);
    }

    /**
     * Get the full HTML representation of the form.
     * Take into account that we may need to group the fields 
     * 
     * @param array $aTableConfig
     * @param array $aDataRowFromDisk
     * @return string HTML
     */
    private function GetHtmlForForm($aTableConfig, $aDataRowFromDisk)
    {
        $cReturn = '';
        if (isset($aTableConfig['groups']) and is_array($aTableConfig['groups']))
        {
            foreach ($aTableConfig['groups'] as $cGroupId => $aGroupConfig)
            {
                // Build the template variables
                $aTemplate = array(
                    'cElements' => $this->GetHtmlForInputElements($aTableConfig['fields'], $aDataRowFromDisk, $cGroupId),
                    'cId' => $this->Idfix->ValidIdentifier($cGroupId),
                    'cTitle' => $aGroupConfig['title'],
                    'cDescription' => $aGroupConfig['description'],
                    'cIcon' => $this->Idfix->GetIconHTML( $aGroupConfig['icon'] ),
                    );
                $cReturn .= $this->Idfix->RenderTemplate('EditFormGroup', $aTemplate);
            }
        } else
        {
            $cReturn .= $this->GetHtmlForInputElements($aTableConfig['fields'], $aDataRowFromDisk);
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
    private function GetHtmlForInputElements($aFieldList, $aDataRowFromDisk, $cGroup = '')
    {
        $cReturn = '';
        foreach ($aFieldList as $cFieldName => $aFieldConfig)
        {
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

            // Value from the table
            $xRawValue = (isset($aDataRowFromDisk[$cFieldName]) ? $aDataRowFromDisk[$cFieldName] : '');
            $xRawPostValue = (isset($_POST[$cFieldName]) ? $_POST[$cFieldName] : '');
            $aFieldConfig['__RawValue'] = $xRawValue;
            $aFieldConfig['__RawPostValue'] = $xRawPostValue;
            $this->Idfix->Event('EditField', $aFieldConfig);
            $cInput = $aFieldConfig['__DisplayValue'];
            $cReturn .= $cInput;
        }
        return $cReturn;
    }
    
    private function GetHtmlHiddenFields( $aTableConfig, $aDataRowFromDisk ){
        $cReturn = '';
        $cReturn .= $this->GetHiddenField( '_checksum', $this->cCheckSum );
        $cReturn .= $this->GetHiddenField( '_tablename', $aTableConfig['_name'] );
        $cReturn .= $this->GetHiddenField( '_configname', $this->Idfix->cConfigName );
        $cReturn .= $this->GetHiddenField( '_return', $_SERVER['HTTP_REFERER'] );
        $cReturn .= "<button class=\"btn btn-primary\" type=\"submit\" value=\"Save\" name=\"_idfix_save_button\">Save</button>&nbsp;";
        $cReturn .= "<button class=\"btn btn-success\" type=\"submit\" value=\"Cancel\" name=\"_idfix_cancel_button\">Cancel</button>";
        return $cReturn;
    }
    private function GetHiddenField( $cName, $cValue) {
        $cName = $this->Idfix->ValidIdentifier($cName);
        $cValue = $this->Idfix->ValidIdentifier($cValue);
        return "<input type=\"hidden\" name=\"{$cName}\" value=\"{$cValue}\">";
    }
}
