<?php

class IdfixFieldsInputFile extends IdfixFieldsInput
{

    public function GetDisplay()
    {
        // Array with the complet file information
        // name: original filename
        // type: Mimetype
        // size: filesize in bytes
        // hash_name: Name that is used for storing the file on server
        $aValue = $this->aData['__RawValue'];

        // Do a quick check for uninitialized values
        if (is_null($aValue) or !isset($aValue['hash_name'])) {
            $this->aData['__DisplayValue'] = '';
            return;
        }


        $cValue = (isset($aValue['name'])) ? $aValue['name'] : '';
        $cMimeType = (isset($aValue['type'])) ? $aValue['type'] : '';
        $cUrl = $this->GetFullFilenameAsUrl($aValue);

        // Is it a picture ??
        //print_r($aValue);
        if (!(strpos($cMimeType, 'image/') === false)) {
            $cValue = "<img src=\"{$cUrl}\" alt=\"{$cValue}\" height=\"30\"  \>";
        }
        $cHref = "<a target=\"_blank\" href=\"{$cUrl}\">{$cValue}</a>";
        $this->aData['__DisplayValue'] = $cHref;
    }

    public function GetEdit()
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        // Unique CSS ID
        $cId = $this->GetId();
        // Unique form input element name
        $cName = $this->GetName();
        // Get CSS class for the input element
        $this->SetCssClass('form-control');
        $this->SetDataElement('id', $cId);
        $this->SetDataElement('name', $cName);

        // Build the attributelist
        $cAttr = $this->GetAttributes($this->aData);

        // And get a reference to the input element
        $cInput = "<input {$cAttr}>";

        $cError = '';

        // Now check the PostValue, if the size of the file is set we have got an upload
        if (isset($this->aData['__RawPostValue']['size']) and $this->aData['__RawPostValue']['size']) {
            // Basic info fromm the $_FILES array
            $aFileInfo = $this->aData['__RawPostValue'];
            // Get any validation messages
            $cError = $this->ValidateFile($aFileInfo);
            // Save this file
            //$this->SaveFile($aFileInfo);
        }

        $this->aData['__DisplayValue'] = $this->RenderFormElement($this->aData['title'], $this->aData['description'], $cError, $cId, $cInput);
        $this->IdfixDebug->Profiler(__method__, 'stop');

    }

    /**
     * Check the file upload
     * 
     * @param mixed $aFileInfo
     * @return
     */
    protected function ValidateFile($aFileInfo)
    {
        $cError = '';
        // Does PHP report an error?
        $this->aData['__ValidationError'] = (boolean)$aFileInfo['error'];

        // Do we exceed the maximum number of bytes set?
        if (isset($this->aData['max'])) {
            $iMax = (integer)($this->aData['max']);
            $iSize = (integer)$aFileInfo['size'];
            if ($iSize > $iMax) {
                $this->aData['__ValidationError'] = 1;
            }
        }

        // get the errormessage
        if ($this->aData['__ValidationError'] and isset($this->aData['error'])) {
            $cError = $this->aData['error'];
        }

        $cError .= $this->SaveFile($aFileInfo);

        return $cError;
    }

    private function SaveFile($aFileInfo)
    {
        //print_r($aFileInfo);
        $cTempFileName = $aFileInfo['tmp_name'];
        // Hash names are based upon the field and tablename so values will be stored together for tables/fields
        $aFileInfo['hash_name'] = md5($this->aData['_name'] . $this->aData['_tablename'] . $this->Idfix->IdfixConfigSalt) . '/' . $aFileInfo['name'];
        $cFullFileName = $this->GetFullFileName($aFileInfo);

        $this->CheckDir($cFullFileName);
        if (!@copy($cTempFileName, $cFullFileName)) {
            $this->aData['__ValidationError'] = 1;
            return $cFullFileName . ' cannot be copied.';
        }

        // Strip Tthe info we do not need
        unset($aFileInfo['error']);
        unset($aFileInfo['tmp_name']);
        $this->aData['__SaveValue'] = $aFileInfo;
    }

    private function GetFullFileName($aFileInfo)
    {
        $cFileName = $aFileInfo['hash_name'];
        $cFilesDir = $this->Idfix->aConfig['filespace'];
        //$cConfigName = $this->Idfix->ValidIdentifier( $this->Idfix->cConfigName );
        return $cFilesDir . "/{$cFileName}";
    }

    private function GetFullFilenameAsUrl($aFileInfo)
    {
        $cFullFileName = $this->GetFullFileName($aFileInfo);
        $cBasePath = $this->ev3->BasePath;
        $cRelativeFilename = str_ireplace($cBasePath, '', $cFullFileName);
        $cRelativeFilename = trim($cRelativeFilename, '/');
        //print_r(get_defined_vars());
        return  $this->ev3->BasePathUrl . '/' . $cRelativeFilename;
    }

    /**
     * Recursive check for a directory structure
     * 
     * @param mixed $cFullFilename
     * @return
     */
    private function CheckDir($cFullFilename)
    {
        $cDirName = dirname($cFullFilename);
        if (!is_writable($cDirName)) {
            $this->CheckDir($cDirName);
            mkdir($cDirName);
        }
        return is_writable($cDirName);
    }


}
