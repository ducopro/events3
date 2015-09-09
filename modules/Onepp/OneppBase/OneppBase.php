<?php

/**
 * Module die een Onepp configuratie in de gaten houdt en op basis daarvan de 1 pagina website
 * bouwt in de cache directory
 * 
 * Daarnaast een handler die op verzoek de gegenereerde pagina oplevert
 */

class OneppBase extends Events3Module {


  /**
   * Catch URLS's with format oneppv/<subdomain>[/<otap>]
   * 
   * @return void
   */
  public function Events3PreRun() {
    $cUrl = (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : $_SERVER['REQUEST_URI']);
    $cCommand = substr(parse_url(urldecode($cUrl), PHP_URL_PATH), 1);
    $aInput = (array )explode('/', $cCommand);
    $cOneppIdentifier = (string )array_shift($aInput);
    $cSubdomain = (string )$this->Idfix->ValidIdentifier(array_shift($aInput));
    $cOtap = (string )array_shift($aInput);

    //echo $cUrl;

    if ($cOneppIdentifier == 'oneppv') {
      $cCacheFile = $this->GetCacheFileName($cSubdomain, $cOtap);

      if (file_exists($cCacheFile)) {
        echo file_get_contents($cCacheFile);
      }
      else {
        echo $cCacheFile;
      }
      exit;
    }
  }


  /**
   * Generate the website if columns/section/site is changed
   */
  public function Events3IdfixSaveRecordDone($aRecord) {
    if ($this->IsCorrectConfig()) {
      // Select the unique ID
      $iId = $aRecord['MainID'];

      //Create a trail from parent ID's
      $aTrail = $this->Idfix->Trail($iId);
      // And get the websiteid
      $iWebSiteId = (integer)array_search(20, $aTrail);

      // Now generate this site all over again
      $this->CreateWebsite($iWebSiteId);

    }
  }


  /**
   * Check if we are running in the context of the correct config
   * 
   * @return
   */
  private function IsCorrectConfig() {
    return (boolean)($this->Idfix->cConfigName == 'onepp');
  }

  private function CreateWebsite($iSiteId, $cOtap = '') {
    // Testje voor de theming
    //$ctest = $this->GetThemedHtml( 'vitality_modern', 'base', array());
    //$ctest = $this->GetThemedHtml( 'vitality_vintage', 'base', array());
    //$ctest = $this->GetThemedHtml( 'vitality', 'base', array());

    $aSiteRecord = $this->IdfixStorage->LoadRecord($iSiteId, false);
    if (isset($aSiteRecord['MainID'])) {
      $iStart = microtime(true);
      // Load all sections, we need 'm multiple times
      $aSections = $this->IdfixStorage->LoadAllRecords(30, $iSiteId, 'Weight');
      //print_r(count($aSections));
      // We postprocess the menu-items a little... so do it here
      $aSiteRecord['_menu'] = $this->GetMenuLinks($aSections);
      $aSiteRecord['_nav'] = $this->GetNavigation($aSiteRecord);
      $aSiteRecord['_assets'] = $this->GetAssetDirUrl($aSiteRecord['Theme']);
      $aSiteRecord['_styles'] = $this->GetSupportColorStyles($aSiteRecord['SupportColor'], $aSiteRecord['Theme']);
      $aSiteRecord['_sections'] = $this->CreateFullBody($aSiteRecord, $aSections);

      $cFullPage = $this->GetThemedHtml($aSiteRecord['Theme'], 'base', $aSiteRecord);
      $cCacheFile = $this->GetCacheFileName($aSiteRecord['Id'], $cOtap);

      // Add a footer to tell us when it was generated
      $fTime = round((microtime(true) - $iStart) * 1000, 2);
      $cDate = date('Y-m-d H:i:s');
      $cFooter = "Cached OnePP Website Created in {$fTime} ms. at {$cDate}";
      $cFooter = "\n\n<!-- {$cFooter} -->";
      $cFullPage .= $cFooter;

      // And save it as a cache file
      file_put_contents($cCacheFile, $cFullPage);

      $this->Idfix->FlashMessage('Cached OnePP Website Created: ' . $cCacheFile . " ({$fTime} ms.)");
    }
  }


  private function GetMenuLinks($aSections) {
    $aLinks = array();
    foreach ($aSections as $iSectionId => $aSectionInfo) {
      // Do we have a menu item?
      if ($aSectionInfo['Menu']) {
        $aLinks[$aSectionInfo['Menu']] = $this->GetSectionIdentifier($aSectionInfo);
      }
    }
    return $aLinks;
  }

  private function GetSectionIdentifier($aSectionInfo) {
    //print_r($aSectionInfo);
    return $this->Idfix->ValidIdentifier('div_' . $aSectionInfo['Menu'] . $aSectionInfo['MainID']);
  }

  private function GetCacheFileName($cSubdomainID, $cOtap = '') {
    if (!stristr(',dev,test,acc,prod,', ',' . $cOtap . ',')) {
      $cOtap = (isset($_GET['otap']) ? $_GET['otap'] : 'prod');
    }

    $cOtap = $this->Idfix->ValidIdentifier($cOtap);
    $cSubdomainID = $this->Idfix->ValidIdentifier($cSubdomainID);
    return $this->ev3->PublicPath . "/onepp/{$cOtap}/{$cSubdomainID}.html";
  }


  private function GetNavigation($aSiteRecord) {
    return $this->GetThemedHtml($aSiteRecord['Theme'], 'navigation', $aSiteRecord);
  }

  private function CreateFullBody($aSiteRecord, $aSections) {
    $cSection = '';
    foreach ($aSections as $iSectionID => $aSectionInfo) {
      $aSectionInfo['_site'] = $aSiteRecord;
      $aSectionInfo['_styles'] = $this->CreateStyles($aSectionInfo);
      $aSectionInfo['_identifier'] = $this->GetSectionIdentifier($aSectionInfo);
      $aSectionInfo['Description'] = $this->PostProcesColumnHref($aSectionInfo['Description'], $aSectionInfo['Section']);

      $cSectionType = $aSectionInfo['Char_1'];

      // Popups are general to the whole theme
      $aSectionInfo['_popups'] = '';
      // Get the block content
      $aSectionInfo['_content'] = '';
      $aColumns = $this->IdfixStorage->LoadAllRecords(40, $iSectionID, 'Weight');
      // Postproces and group the categories, create valid identifiers from the category names
      $aSectionInfo['_cats'] = $this->CreateCats($aColumns);
      
      $iColumnCount = count($aColumns);
      foreach ($aColumns as $iColumnId => $aColumnInfo) {
        // Only render a popup if there is a need because of detailed information
        $bShowPopups = (boolean)strip_tags($aColumnInfo['Text_1']);
        $aColumnInfo['_popup_id'] = ($bShowPopups ? 'popup-' . $iColumnId : '');
        $aColumnInfo['_smi'] = $this->GetSocialMediaIcons($aColumnInfo);
        // Generate an URL
        $aColumnInfo['Description'] = $this->PostProcesColumnHref($aColumnInfo['Description'], $aColumnInfo['Section']);
        // Pictures can be uploaded of set by link
        $aColumnInfo['_image'] = $this->GetPictureUrl($aColumnInfo);
        $aColumnInfo['_columns'] = $this->GetColumnClasses($aSectionInfo['BG_column_width'], $aColumnInfo['Int_2'], $aColumnInfo['Int_1'], $iColumnCount);
        $aColumnInfo['_columncount'] = $iColumnCount;
        $aColumnInfo['_columnwidth'] = (int)$aColumnInfo['Int_2'];
        $aColumnInfo['_section'] = $aSectionInfo;
        $aColumnInfo['_site'] = $aSiteRecord;
        $aSectionInfo['_content'] .= $this->GetThemedHtml($aSiteRecord['Theme'], "section_column_{$cSectionType}", $aColumnInfo);
        if ($bShowPopups) {
          $aSectionInfo['_popups'] .= $this->GetThemedHtml($aSiteRecord['Theme'], 'popup', $aColumnInfo);
        }
      }
      
      $aSectionInfo['_columncount'] = $iColumnCount;
      $cSection .= $this->GetThemedHtml($aSiteRecord['Theme'], "section_{$cSectionType}", $aSectionInfo);
    }
    //$this->log(get_defined_vars());
    return $cSection;
  }

  /**
   * If an internal section href is set, this takes presedence.
   * 
   * @param mixed $cHref
   * @param mixed $iSectionId
   * @return
   */
  private function PostProcesColumnHref($cHref, $iSectionId) {
    if ($iSectionId) {
      $aSection = $this->IdfixStorage->LoadRecord($iSectionId);
      $cHref = '#' . $this->GetSectionIdentifier($aSection);
    }
    return $cHref;
  }
  /**
   * OneppBase::GetColumnClasses()
   * 
   * Set the correct column classes and calculate some intelligent default values.
   * 
   * @param mixed $iDefaultWidth
   * @param mixed $iColumWidth
   * @param mixed $iColumnOffset
   * @param mixed $iColumnCount
   * @return
   */
  private function GetColumnClasses($iDefaultWidth, $iColumWidth, $iColumnOffset, $iColumnCount) {
    // Calculate in intelligent column width default based on the number of columns
    $iCalculatedDefault = 4;
    if ($iColumnCount) {
      $iCalculatedDefault = (integer)floor(12 / $iColumnCount);

    }
    // Make integers
    $iDefaultWidth = (integer)$iDefaultWidth;
    $iColumWidth = (integer)$iColumWidth;
    $iColumnOffset = (integer)$iColumnOffset;
    // Check all values
    $iDefaultWidth = (($iDefaultWidth < 1 or $iDefaultWidth > 12) ? $iCalculatedDefault : $iDefaultWidth);
    $iColumWidth = (($iColumWidth > 0 and $iColumWidth <= 12) ? $iColumWidth : $iDefaultWidth);
    //Set classes
    $cClasses = 'col-lg-' . $iColumWidth;
    if ($iColumnOffset and $iColumnOffset <= 12) {
      $cClasses .= ' col-lg-offset-' . $iColumnOffset;
    }
    return $cClasses;
  }
  /**
   * Create the generic styles for the section
   * 
   * @param mixed $aSection
   * @return
   */
  private function CreateStyles($aSection) {
    $cStyles = '';

    $cBackground = $this->GetPictureUrl($aSection);
    if ($cBackground) {
      $cStyles .= "background-image:url({$cBackground});\nbackground-size: cover;\n";
    }
    else {
      $cStyles .= "background-image:none;\n";
    }

    // First check if we need a rule for the background color
    // Does it start with a hash and is the number bigger than 0
    // But no color if we have a picture bacjkground
    $cColorCode = trim($aSection['BG_color']);
    if (!$cBackground and $this->IsValidColorCode($cColorCode)) {
      $cStyles .= "background-color:{$cColorCode};\n";
    }
    else {
      $cStyles .= "background-color:none;\n";
    }


    // Check if we need a height. It is always in EM
    $iHeight = (integer)$aSection['BG_height'];
    if ($iHeight) {
      $cStyles .= "height:{$iHeight}em;\n";
    }

    return $cStyles;

  }

  /**
   * Get a simple array with the social media identifier 
   * as the key and the URL as the value.
   * 
   * All fields are prefixed smi_ in the configuration followed by the
   * name of the social media brand is used in the FA library
   * 
   * @param array $aColumnInfo
   * @return array Icon Name -> Social Media URL
   */
  private function GetSocialMediaIcons($aColumnInfo) {
    $aIcons = array();
    foreach ($aColumnInfo as $cName => $cUrl) {
      if ($cUrl and (substr($cName, 0, 4) == 'smi_')) {
        $aIcons[str_replace('_', '-', substr($cName, 4))] = $cUrl;
      }
    }
    return $aIcons;
  }

  private function GetPictureUrl($aSection) {
    $cUpload = isset($aSection['BG_picture']) ? $aSection['BG_picture']['url'] : '';
    $cUrl = $aSection['BG_Url'];
    $cBackground = $cUrl ? $cUrl : ($cUpload ? $cUpload : '');
    return $cBackground;
  }

  private function GetSupportColorStyles($cColor, $cTheme) {
    $cStyles = '';
    if ($this->IsValidColorCode($cColor)) {
      $cCssFile = $this->GetAssetDir($cTheme) . 'color.css';
      if (file_exists($cCssFile)) {
        $cStyles = file_get_contents($cCssFile);
        $cStyles = '<style>' . str_ireplace('%color%', $cColor, $cStyles) . '</style>';
      }
    }
    return $cStyles;
  }

  /**
   * Check if this is a valid colorcode and not black
   * 
   * @param char $cColorCode
   * @return boolean
   */
  private function IsValidColorCode($cColorCode) {
    $cColorCode = trim($cColorCode);
    $bIsHex = (boolean)(substr($cColorCode, 0, 1) == '#');
    $bIsColor = (boolean)(substr($cColorCode, 1, 6) != '000000');
    $bIsLenght = (strlen($cColorCode) == 7);
    return $bIsColor and $bIsHex and $bIsLenght;
  }

  /**
   * Workhorse method for getting themed content.
   * All calls for themed content are routed through this function.
   * If a specific template is not found, it is searched for in the parent
   * theme up the directory hierarchuy.
   * 
   * If no template is found an empty string is returned and no errors generated
   * 
   * @param string $cThemeName
   * @param string $cTemplateId
   * @param array $aVariables
   * @return string Themed HTML as returned by the template or empty if no template is found
   */
  private function GetThemedHtml($cThemeName, $cTemplateId, $aVariables) {
    $cHtml = '';
    $cTemplateFile = $this->GetFileFromTheme($cThemeName, $cTemplateId);
    $this->log($cTemplateFile);
    // file_exists() is already done
    if ($cTemplateFile) {
      $cHtml = $this->Template->Render($cTemplateFile, $aVariables);
    }
    return $cHtml;
  }

  /**
   * Get a full filename from the theme. If it is not found
   * check the parent theme and return that one.
   * 
   * @param string $cThemeName
   * @param string $cTemplateId
   * @return Existing templatefile or empty string
   */
  private function GetFileFromTheme($cThemeName, $cTemplateId) {
    $cTemplateFileName = '';
    $cBaseDir = dirname(__file__) . '/themes/';
    // Add filename to get a correct directoryname with: dirname()
    $cThemeDir = $this->GetThemeDirRecursive($cBaseDir, $cThemeName) . 'dummy.tmp';
    $this->log($cThemeDir);
    do {
      // Result is a dirname without trailing backslash
      $cThemeDir = dirname($cThemeDir);
      $this->log($cThemeDir);
      $cCheckTemplateFile = $cThemeDir . '/' . $cTemplateId . '.php';
      if (file_exists($cCheckTemplateFile)) {
        $cTemplateFileName = $cCheckTemplateFile;
        break;
      }
    } while (is_dir($cThemeDir) and (strlen($cThemeDir) > strlen($cBaseDir)));
    return $cTemplateFileName;
  }

  private function GetThemeDirectory($cName) {
    return dirname(__file__) . '/themes/' . $cName . '/';
  }

  /**
   * Get a themedirectory, check all subthemes also
   * 
   * @param string $cBaseDir
   * @param string $cName
   * @return string full directory if found, empty string if not found
   */
  private function GetThemeDirRecursive($cBaseDir, $cName) {
    static $cCache = null;
    if (!is_null($cCache)) {
      return $cCache;
    }

    $cThemeDir = '';
    $cCheckDir = $cBaseDir . $cName . '/';
    if (is_dir($cCheckDir)) {
      $cThemeDir = $cCheckDir;
    }
    else {
      // Check all subdirectories
      $aDirs = (array )glob($cBaseDir . '*', GLOB_ONLYDIR);
      foreach ($aDirs as $cDir2Check) {
        $cDirname = $this->GetThemeDirRecursive($cDir2Check . '/', $cName);
        if ($cDirname) {
          // We found our target!!!
          $cThemeDir = $cDirname;
        }
      }

    }

    if ($cThemeDir) {
      $cCache = $cThemeDir;
    }

    return $cThemeDir;
  }

  private function GetAssetDirUrl($cThemeName) {
    $cAssetDir = $this->GetAssetDir($cThemeName);
    $cUrl = str_ireplace($this->ev3->BasePath, $this->ev3->BasePathUrl, $cAssetDir);
    $cUrl = str_replace('\\', '/', $cUrl);
    return $cUrl;
  }
  
  private function GetAssetDir($cThemeName) {
    $cAssetDir = dirname(__file__) . "/themes/{$cThemeName}/assets/";
    $cBaseDir = dirname(__file__) . '/themes/';
    // Add filename to get a correct directoryname with: dirname()
    $cThemeDir = $this->GetThemeDirRecursive($cBaseDir, $cThemeName) . 'dummy.tmp';
    //$this->log($cThemeDir);
    do {
      // Result is a dirname without trailing backslash
      $cThemeDir = dirname($cThemeDir);
      $cAssetDir = $cThemeDir . '/assets/';
      if (is_dir($cAssetDir)) {
        break;
      }
    } while (is_dir($cThemeDir) and (strlen($cThemeDir) > strlen($cBaseDir)));
    return $cAssetDir;
  }

  /**
   * Postprocess the columns and create a nice array contaning
   * the category names as keys and the number of items as the value
   * 
   * Side effect:
   * Category names are postprocessed into valid identifiers
   * 
   * @param array $aColumns
   * @return array
   */
  private function CreateCats(&$aColumns){
     $aCats = array();
     foreach($aColumns as &$aColumn) {
      if(!isset($aColumn['Category']) or !$aColumn['Category']) {
         continue;
      }
      // Postproces and cleanup the name
      $aColumn['Category'] = $this->Idfix->ValidIdentifier($aColumn['Category']);
      $cCat =  $aColumn['Category'];
      if(isset($aCats[$cCat])) {
         $aCats[$cCat]++;
      }
      else {
         $aCats[$cCat] = 1;
      }
     }
     return $aCats; 
  }

}
