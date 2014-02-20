<?php

/**
 * Idfix File Parser
 */

class IdfixParse extends Events3Module
{
    
    public function Events3IdfixGetConfig() {
       $oIdfix = $this->load('Idfix');
       $cConfigName = $oIdfix->cConfigName;
       $cFileName = dirname(__FILE__) . '/configs/' . $cConfigName . '.idfix';
       if (file_exists($cFileName) and is_readable($cFileName)) {
        $oIdfix->aConfig = $this->Parse($cFileName);
       }
    }
    
   
    /**
     * P A R S I N G   S Y S T E M
     * 
     */
      
    /**
     * Parse()
     *
     * Parse an idfix configuration file
     * Keyproint for the configuration is flexibility,
     * ease of use and forgiving syntax
     *
     *
     * @param
     *   mixed $cFilename
     * @param
     *   integer $ilevel (only used with recursion)
     * @return
     *   multilevel array
     */
    public function Parse($cFilename, $ilevel = -1)
    {
        $cRetval = array();

        while (true)
        {
            $cRow = $this->RowReader($cFilename);
            // Exit if no row found
            if (!$cRow)
            {
                break;
            }

            // Check if it is an array
            if ($cRow['token'] == '#' and $cRow['key'])
            {
                // If it is indented, we have a new structure
                if ($cRow['level'] > $ilevel)
                {
                    // Create new array
                    $cRetval[$cRow['key']] = $this->Parse($cFilename, $cRow['level']);
                }
                // otherwise we need to go back a level
                else
                {
                    // Signal that the row is not yet parsed
                    // and needs to be analyzed again
                    $this->RowReader($cFilename, true);
                    break;
                }

            }
            // Check if it is an item
            elseif ($cRow['token'] == '-')
            {
                // If key and value are the same, the rowreader has detected
                // just a single value after the token. In that case we rather
                // have a default numeric indexed arraykey
                if ($cRow['key'] == $cRow['value'])
                {
                    $cRetval[] = $cRow['value'];
                }
                // If there's no key, noo need to store this value
                elseif ($cRow['key'])
                {
                    $cRetval[$cRow['key']] = $cRow['value'];
                }
            }
            // None of these conditions?
            // Than it's whitespace or comment
        }

        // Close the file if we are on the main entry level
        if ($ilevel === -1)
        {
            $this->RowReader('RESET');
        }

        return $cRetval;
    }

    /**
     * RowReader()
     *
     * Read one row from an idfix configutration file
     * and return the analysed row as a structured array
     *
     * @param
     *   mixed $cFilename
     * @param
     *   bool $setflag
     * @return
     *   Row that was read from the file
     */
    private function RowReader($cFilename, $setflag = false)
    {
        static $fp = null;
        static $bReadLineAgain = false;
        static $prev_row = array();

        // If filename is NULL we need to close the fiel and
        // reset the filhandle for the next file
        if ($cFilename === 'RESET' and $fp)
        {
            fclose($fp);
            $fp = null;
            return;
        }

        // We only set the flag if asked
        if ($setflag)
        {
            $bReadLineAgain = true;
            return;
        }

        // Need the line again?
        if ($bReadLineAgain)
        {
            $bReadLineAgain = false;
            return $prev_row;
        }

        $cRetval = null;
        // Open file
        if (is_NULL($fp))
        {
            $fp = fopen($cFilename, 'r');
        }

        // Read next row
        if ($fp)
        {
            if ($cLine = fgets($fp, 4096))
            {
                $cRetval = $this->LineParser($cLine);
                // Store it in case we need it again
                $prev_row = $cRetval;
            }
        }

        return $cRetval;
    }

    /**
     * LineParser()
     *
     * Parse a line from an idfix configuration
     *
     * Line has the following structure:
     * [optional whitespace][token # or -][optional identifier][optional =][value]
     *
     * If there is no token the line is handled as comment
     * Inline comments are NOT allowed at this moment
     *
     * @param
     *   mixed $cLine
     * @return
     *   Tokenized line
     */
    private function LineParser($cLine)
    {
        $cToken = (string )substr(trim($cLine), 0, 1);
        if (!$cToken)
        {
            $cToken = ' ';
        }
        $ilevel = (integer)strpos($cLine, $cToken);
        $data = trim(substr($cLine, $ilevel + 1));

        // Check separator for $xKey/value pair
        if ($cSeparator = strpos($data, '='))
        {
            $xKey = substr($data, 0, $cSeparator);
            $value = substr($data, $cSeparator + 1);
        } else
        {
            $xKey = $data;
            $value = $data;
        }

        // Make the key an integer if needed
        if (is_numeric($xKey))
        {
            $xKey = (integer)$xKey;
        }
        // ... otherwise turn the key into a valid identifier
        else
        {
            $blacklist = str_ireplace(str_split('abcdefghijklmnopqrstuvwxyz_1234567890'), '', $xKey);
            if ($blacklist)
            {
                $xKey = str_replace(str_split($blacklist), '_', $xKey);
            }
            if (is_numeric(substr($xKey, 0, 1)))
            {
                $xKey = '_' . $xKey;
            }
        }


        return array(
            'token' => $cToken,
            'level' => $ilevel,
            'key' => $xKey,
            'value' => $value);
    }
}

