<?php

/**
 * Filtering and Searching
 * 
 */

class IdfixFilter extends Events3Module
{

    /**
     * Event that is triggered before the actionlist is created.
     * So we have time to set up all the filters we need and remove
     * filters we do not want.
     * 
     * After this the list is rendered in it's new state.
     * 
     * @param mixed $output
     * @return void
     */
    public function Events3IdfixActionListBefore(&$output)
    {
        $bAdd = (isset($_GET['filter']) and substr($_GET['filter'], 0, 3) == 'add');
        $bClear = (isset($_GET['filter']) and substr($_GET['filter'], 0, 5) == 'clear');
        $bClearAll = (isset($_GET['filter']) and substr($_GET['filter'], 0, 8) == 'clearall');
        $bPosted = (count($_POST) > 0);

        if ($bAdd) {
            // Get and show the form
            $output .= $this->GetForm();
        }
        elseif ($bPosted) {
            $this->SetFilter();
        }
        elseif ($bClearAll) {
            $this->FilterList(-1);
        }
        elseif ($bClear) {
            $iId = (integer)substr($_GET['filter'], 6);
            $this->FilterList($iId);
        }


        //print_r(get_defined_vars());
    }

    /**
     * Called when values are posted from the filterform
     * 
     * @return void
     */
    private function SetFilter()
    {
        $cFieldName = $this->Idfix->cFieldName;
        if (isset($_POST['button-ok'])) {
            // Do we have a configuration?
            if (isset($this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['search'][$cFieldName])) {
                $aSearchConfig = $this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['search'][$cFieldName];
                // Defaults to 0, needed for checkboxes
                $xRawPostValue = isset($_POST[$cFieldName]) ? $_POST[$cFieldName] : 0;
                // Sety the postvalue
                $aSearchConfig['__RawPostValue'] = $xRawPostValue;
                // Trigger the event
                $this->Idfix->Event('EditField', $aSearchConfig);
                // Now we have an ok value
                $xValue = $aSearchConfig['__SaveValue'];
                $this->FilterList($aSearchConfig);
            }


        }

    }

    /**
     * Save and restore the filterlist
     * 
     * @param mixed $aSearchConfig
     *   null Just return the filterlist
     *   -1 Clear the whole list
     *   0 > Numeric, clear that item from the list
     *   array Set it in the list
     * @return
     */
    private function FilterList($aSearchConfig = null)
    {
        // By creating the sessionkey for the table and the parent we are sure
        // that each page has it's own filtering
        // Maybe remove the parent???? or make it configurable??? Better!!
        // something like:
        /**
         * #tables
         *  -table1
         *      -filter=shared
         *  -table2
         *      -filter=unique
         */
        $cSessionKey = __method__ . $this->Idfix->cTableName . $this->Idfix->iParent;

        // Default value for the list, or reset it.
        if (!isset($_SESSION[$cSessionKey]) or !is_array($_SESSION[$cSessionKey]) or $aSearchConfig === -1) {
            $_SESSION[$cSessionKey] = array();
        }

        if (is_array($aSearchConfig)) {
            $_SESSION[$cSessionKey][] = $aSearchConfig;
        }
        elseif (isset($_SESSION[$cSessionKey][$aSearchConfig])) {
            unset($_SESSION[$cSessionKey][$aSearchConfig]);
        }

        return $_SESSION[$cSessionKey];
    }

    /**
     * Return the form where a value can be set.
     * 
     * @return
     */
    private function GetForm()
    {
        $cForm = '';
        $cFieldName = $this->Idfix->cFieldName;
        //echo $this->Idfix->GetUrl();
        if (isset($this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['search'][$cFieldName])) {
            // Field Configuratiom
            $aSearchConfig = $this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['search'][$cFieldName];
            // Do some postprocessing
            $aSearchConfig = $this->Idfix->PostprocesConfig($aSearchConfig);
            // Trigger the standard event for an editfield
            $this->Idfix->Event('EditField', $aSearchConfig);
            // And build the templatevariables
            $aTemplateVars = array(
                'cInput' => $aSearchConfig['__DisplayValue'],
                'cTitle' => $aSearchConfig['title'],
                'cDescription' => $aSearchConfig['description'],
                'cUrl' => $this->Idfix->GetUrl(),
                );
            $cForm = $this->Idfix->RenderTemplate('ActionListFilter', $aTemplateVars);
        }


        return $cForm;
    }

    /**
     * Implements event Navbar
     * Create an extra option in the dropdown menu
     * 
     * @param mixed $aData
     * @return void
     */
    public function Events3IdfixNavbar(&$aData)
    {
        // Only create this structure when in list mode
        if ($this->Idfix->cAction !== 'List') {
            return;
        }

        // Basic list with searchfield information
        $aSearchList = array();
        if (isset($this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['search'])) {
            $aSearchList = $this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['search'];
        }

        // Create the datastructure for the dropdown
        $aDropdown = array();

        /// Start Remove filter section
        $aFilters = $this->FilterList();
        $iFilterCount = count($aFilters);
        if ($iFilterCount > 0) {
            // Header for disabling all Filtering
            $aDropdown[] = array(
                'type' => 'header',
                'title' => 'Cancel filtering',
                'icon' => $this->Idfix->GetIconHTML('remove-circle'));

            // Signal for the filter module
            $aAttr = array('filter' => 'clearall');
            $aDropdown[] = array(
                'title' => 'Clear all',
                'href' => $this->Idfix->GetUrl('', '', '', null, null, 'list', $aAttr), //
                'tooltip' => 'Remove all filtering.',
                'type' => 'normal',
                'icon' => $this->Idfix->GetIconHTML('off'),
                );

            foreach ($aFilters as $iId => $aConfig) {
                $aAttr = array('filter' => 'clear/' . $iId);
                $cTitle = $aConfig['title'] . ' [="' . $aConfig['__SaveValue'] . '"]';
                $aDropdown[] = array(
                    'title' => 'Clear ' . $cTitle, //$aSearchList[$cFieldName]['title'],
                    'href' => $this->Idfix->GetUrl('', '', $aConfig['_name'], null, null, 'list', $aAttr), //
                    'tooltip' => 'Remove filter for ' . $cTitle,
                    'type' => 'normal',
                    'icon' => $this->Idfix->GetIconHTML($aConfig),
                    );

            }
            $aDropdown[] = array('type' => 'divider');
        }


        // Header for new filters
        $aDropdown[] = array(
            'type' => 'header',
            'title' => 'Start filtering',
            'icon' => $this->Idfix->GetIconHTML('plus'));


        foreach ($aSearchList as $cFieldName => $aSearchConfig) {
            $aAttr = array('filter' => 'add');
            $aDropdown[] = array(
                'title' => $aSearchConfig['title'],
                'href' => $this->Idfix->GetUrl('', '', $cFieldName, null, null, 'list', $aAttr), //
                'tooltip' => 'Create new filter on this field.',
                'type' => 'normal',
                'icon' => $this->Idfix->GetIconHTML($aSearchConfig),
                );

        }


        // Add a batch for the number of filters
        $cBadge = '';
        if ($iCount = count($this->FilterList())) {
            $cBadge = '<span class="badge">' . $iCount . '</span>';
        }

        $aData['left'][] = array(
            'title' => 'Filter ' . $cBadge,
            'tooltip' => 'Filter the list on one or more fields.',
            'href' => '#',
            'dropdown' => $aDropdown,
            'icon' => $this->Idfix->GetIconHTML('filter'),
            );

    }

    function Events3IdfixListDataSet(&$aData)
    {
        // get a reference to the where clauses
        $aWhere = &$aData['where'];
        $aFilters = $this->FilterList();
        foreach ($aFilters as $aSearchConfig) {
            $cSqlTemplate = $aSearchConfig['sql'];
            $cValue = $this->Database->quote($aSearchConfig['__SaveValue']);
            // Remove the single quotes, it is set in the template
            $cValue = trim($cValue, "'");
            // Add the value to the template
            $cSql = str_ireplace('%value', $cValue, $cSqlTemplate);
            // And add it to the where clauses
            $aWhere[] = $cSql;
        }

    }

}
