<?php

class IdfixSort extends Events3Module
{

    // Internal fieldname to sort on
    public $cFieldName = '';
    // Ascending or descending
    public $cDirection = '';
    // Key for the url attribute
    const SORTKEY = 'sort';
    const ASCENDING = '/a';
    const DESCENDING = '/d';


    /**
     * Check in an early stage if there is sorting information available in the url
     * If so, parse and store it.
     * So it can be set in the url as needed
     * 
     * @return void
     */
    public function Events3PreRun()
    {
        // Dow we have sorting information in the url???
        if (isset($_GET[self::SORTKEY]))
        {
            $cMessage = urldecode($_GET[self::SORTKEY]);
            if (strpos($cMessage, self::ASCENDING))
            {
                // Set the direction
                $this->cDirection = self::ASCENDING;
                // and strip the info from the url
                $cMessage = str_replace(self::ASCENDING, '', $cMessage);
            } else
            {
                $this->cDirection = self::DESCENDING;
                $cMessage = str_replace(self::DESCENDING, '', $cMessage);
            }
            // What we got left is the fieldname to sort on
            $this->cFieldName = $cMessage;
        }
    }

    /**
     * Implements Event ListHeader as triggered from the IdfixList module
     * Set the right icon on the tableheader.
     * 
     * @param mixed $aHeader
     * @return void
     */
    public function Events3IdfixListHeader(&$aHeader)
    {
        if (is_array($aHeader))
        {
            foreach ($aHeader as $cFieldName => $cDisplayName)
            {
                // Default sort order when clicked
                $cClickSortOrder = 'a';
                if ($cFieldName == $this->cFieldName)
                {
                    $cClickSortOrder = ($this->cDirection == self::ASCENDING) ? 'd' : 'a';
                    $cIcon = ($this->cDirection == self::ASCENDING) ? 'sort-by-attributes' : 'sort-by-attributes-alt';
                    $cIconHtml = $this->Idfix->GetIconHTML($cIcon);
                    $aHeader[$cFieldName] = $cIconHtml . '&nbsp;' . $cDisplayName;
                }
                
                // Is this a field we can sort on???
                if( $this->CheckSortFieldname($cFieldName)) {
                    $cUrl = $this->Idfix->GetUrl(null,null,null,null,null,null,array('sort'=>$this->CreateUrlAttribute($cFieldName,$cClickSortOrder)));
                    $aHeader[$cFieldName] = "<a href=\"{$cUrl}\">{$aHeader[$cFieldName]}</a>";
                }
                
            }
        }
    }

    /**
     * Add a correct sort clause to the SQL for the listing of the dataset
     * 
     * @param mixed $aParams
     * @return void
     */
    public function Events3IdfixListDataSet(&$aParams)
    {
        // Do we have a correct SQL field?
        if($this->CheckSortFieldname( $this->cFieldName)) {
            
            $cOrder = ($this->cDirection==self::ASCENDING) ? ' ASC' : ' DESC';
            $aParams['order'] = $this->cFieldName . $cOrder;
            //echo $aParams['sort'];
        }
    }

    /**
     * Check if the field we need to sort on is really a good
     * fieldname.
     * We check the sort array here. Everything in the sort array
     * is already checked against the structure of the table in the
     * IdfixDefault module.
     * 
     * @param mixed $cCheck
     * @return
     */
    private function CheckSortFieldname($cCheck)
    {
        $bReturn = false;
        if (isset($this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['sort']))
        {
            $aSortConfig = $this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['sort'];
            foreach ($aSortConfig as $cFieldName => $cSortOrder)
            {
                if (is_numeric($cFieldName))
                {
                    $cFieldName = $cSortOrder;
                }
                // If the fields match qwe can skip this loop, everything is ok.
                if( $cFieldName == $cCheck) {
                    return true;
                }
                
            }
        }
        return $bReturn;
    }

    /**
     * Add a sort key to the querystring as needed
     * 
     * @param mixed $aParams
     * @return void
     */
    public function Events3IdfixGetUrl(&$aParams)
    {
        // If we already specified a sortkey us that one
        if (!isset($aParams[self::SORTKEY]))
        {
            if ($this->cFieldName)
            {
                // Ok, there was a sort parameter in the calling url
                // Add it again
                $aParams[self::SORTKEY] = $this->CreateUrlAttribute();
            } else
            {
                // Maybe there is a default sort order set
                // in the configuration? Use it.
                if (isset($this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['sort']))
                {
                    $aSortConfig = $this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['sort'];
                    foreach ($aSortConfig as $cFieldName => $cSortOrder)
                    {
                        /**
                         * #sort
                         *  -MainId
                         *  -SubTypeID=asc
                         * 
                         * So if no sort order is specified the key of the array will be numeric!
                         */
                        if (!is_numeric($cFieldName))
                        {
                            $aParams[self::SORTKEY] = $this->CreateUrlAttribute($cFieldName, $cSortOrder);
                        }
                    }
                }
            }
        }
    }

    /**
     * Isfix event handler for the navigation bar
     * 
     * @param mixed $data
     * @return void
     */
    public function Events3IdfixNavbar(&$data)
    {
        // Only create this structure when in list mode
        if ($this->Idfix->cAction !== 'List')
        {
            return;
        }


        // Create a dropdown structure for showing all the possible
        // sort options
        $aSortList = array();
        $aFieldList = array();
        if (isset($this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['sort']))
        {
            $aSortList = $this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['sort'];
            $aFieldList = $this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['fields'];
        }
        //print_r($aSortList);
        $aDropdown = array();

        // Ascending sorts
        $aDropdown[] = array(
            'type' => 'header',
            'title' => 'Ascending',
            'icon' => $this->Idfix->GetIconHTML('sort-by-attributes'));
        foreach ($aSortList as $cFieldName => $cSortInfo)
        {
            // First let's get the correct fieldname
            $cFieldName = (is_numeric($cFieldName)) ? $cSortInfo : $cFieldName;
            // Than create the sort attributes for the url
            $aAttr = array(self::SORTKEY => $this->CreateUrlAttribute($cFieldName, 'a'));
            // Now do a check if this is the current sort order!!
            $bActive = ($this->cFieldName == $cFieldName and $this->cDirection == self::ASCENDING);

            $aDropdown[] = array(
                'title' => isset($aFieldList[$cFieldName]['title']) ? $aFieldList[$cFieldName]['title'] : $cFieldName,
                'href' => $this->Idfix->GetUrl('', '', '', null, null, 'list', $aAttr), //
                'tooltip' => isset($aFieldList[$cFieldName]['description']) ? $aFieldList[$cFieldName]['description'] : '',
                'type' => 'normal',
                'icon' => '',
                'active' => $bActive,
                );
        }
        // Descending sorts
        $aDropdown[] = array('type' => 'divider');
        $aDropdown[] = array(
            'type' => 'header',
            'title' => 'Descending',
            'icon' => $this->Idfix->GetIconHTML('sort-by-attributes-alt'));

        foreach ($aSortList as $cFieldName => $cSortInfo)
        {
            // First let's get the correct fieldname
            $cFieldName = (is_numeric($cFieldName)) ? $cSortInfo : $cFieldName;
            // Than create the sort attributes for the url
            $aAttr = array(self::SORTKEY => $this->CreateUrlAttribute($cFieldName, 'd'));
            // Now do a check if this is the current sort order!!
            $bActive = ($this->cFieldName == $cFieldName and $this->cDirection == self::DESCENDING);

            $aDropdown[] = array(
                'title' => isset($aFieldList[$cFieldName]['title']) ? $aFieldList[$cFieldName]['title'] : $cFieldName,
                'href' => $this->Idfix->GetUrl('', '', '', null, null, 'list', $aAttr), //
                'tooltip' => isset($aFieldList[$cFieldName]['description']) ? $aFieldList[$cFieldName]['description'] : '',
                'type' => 'normal',
                'icon' => '',
                'active' => $bActive,
                );
        }

        $data['left'][] = array(
            'title' => 'Sort',
            'tooltip' => 'Sort on one of the system fields. Click here to reset the sorting to default.',
            'href' => '#',
            'dropdown' => $aDropdown,
            'icon' => $this->Idfix->GetIconHTML('sort'),
            );


    }

    /**
     * Create an attributestring to be added to the url
     * 
     * @param string $cFieldName
     * @param string $cOrder
     * @return void
     */
    private function CreateUrlAttribute($cFieldName = '', $cOrder = '')
    {
        $cFieldName = ($cFieldName) ? $cFieldName : $this->cFieldName;
        $cOrderFromParameter = (substr(strtolower($cOrder), 0, 1) == 'a') ? self::ASCENDING : self::DESCENDING;
        $cOrder = ($cOrder) ? $cOrderFromParameter : $this->cDirection;
        // Example: &sort=MainID/a
        return ($cFieldName . $cOrder);
    }


}
