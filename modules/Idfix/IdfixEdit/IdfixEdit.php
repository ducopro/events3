<?php

/**
 * Functionality for rendering edity forms and storing POST information
 * 
 */
class IdfixEdit extends Events3Module
{

    public $oIdfix, $oIdfixStorage;

    public function Events3IdfixActionEdit()
    {
        // Id of the record we need to edit
        $iMainID = $this->Idfix->iObject;
        // Fullly loaded record from disk
        $aDataRowFromDisk = $this->IdfixStorage->LoadRecord($iMainID);
        // And get to the table configuration
        $cConfigName = $this->Idfix->cConfigName;
        $cTableName = $this->Idfix->cTableName;
        $aTableConfig = $this->Idfix->aConfig['tables'][$cTableName];

        $cHtmlInputForm = $this->GetHtmlForForm($aTableConfig, $aDataRowFromDisk);

        // Now wrap the raw html in it's form tag and add some hidden fields
        $aTemplate = array(
          'iMainID' => $iMainID,
        );

        echo $cHtmlInputForm;
    }

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
                    'cId' => $this->Idfix->ValidIdentifier($cId),
                    'cTitle' => $aGroupConfig['title'],
                    'cDescription' => $aGroupConfig['description'],
                    'cIcon' => $aGroupConfig['icon'],
                    );
                $cReturn .= $this->idfix->RenderTemplate('FormGroup', $aTemplate);
            }
        } else
        {
            $cReturn .= $this->GetHtmlForInputElements($aTableConfig['fields'], $aDataRowFromDisk);
        }
        return $cReturn;
    }
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

            $aFieldConfig['__RawValue'] = $aDataRowFromDisk[$cFieldName];
            $this->Idfix->Event('EditField', $aFieldConfig);
            $cInput = $aFieldConfig['__DisplayValue'];
            $cReturn .= $cInput;
        }
        return $cReturn;
    }
}
