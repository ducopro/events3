<?php

/**
 * The Idfix module is the dispathhing engine for building
 * a modular system.
 * 
 */
class Idfix extends Events3Module {
  // Command line parameter values
  public $aConfig, $cConfigName, $cTableName, $cFieldName, $iObject, $iParent, $cAction;

  // Stubs for the configuration properties
  // Main purpose for this declaration is code completion.
  public $IdfixConfigSalt, $IdfixConfigCache, $IdfixConfigCleanUrls;

  // Entry in $_SESSION array for caching
  const CACHE_KEY = '__idfix_cache__';

  // Permissions
  const PERM_ACCESS_INFOPANEL = 'idfix_access_infopanel';

  /**
   * Configuration settings that can be overruled
   * in the configurationfile
   * 
   * @param array &$aConfig Reference to the configuration array
   * @return void
   */
  public function Events3ConfigInit(&$aConfig) {
    $cKey = 'IdfixConfigSalt';
    $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : md5(time());
    $this->$cKey = $aConfig[$cKey];

    $cKey = 'IdfixConfigCache';
    $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : 1;
    $this->$cKey = $aConfig[$cKey];

  }

  /**
   * Run event and main entry for the IDFIX system
   * 
   * @param string $cIdfixCommands, Commandline parameters tos use instead of the values from $_GET
   * @return void
   */
  public function Events3Run() {
    //$this->IdfixDebug->Profiler(__method__, 'start');

    // Default values from the url
    $cCommand = substr(parse_url(urldecode($_SERVER['PATH_INFO']), PHP_URL_PATH), 1);
    //$this->log(get_defined_vars());
    if (!$cCommand) {
      return;
    }

    // What do we need to do?
    $aInput = (array )explode('/', $cCommand);

    // This way we have some intelligent defaults
    // which may be empty of course
    $cConfigName = (string )array_shift($aInput);
    $cTableName = (string )array_shift($aInput);
    $cFieldName = (string )array_shift($aInput);
    $iObject = (integer)array_shift($aInput);
    $iParent = (integer)array_shift($aInput);
    $cAction = (string )array_shift($aInput);

    $content = $this->Render($cConfigName, $cTableName, $cFieldName, $iObject, $iParent, $cAction, $aInput);
    $navbar = $this->RenderNavBar();
    // Get all the rendered messages
    $messages = $this->FlashMessage();

    // And wrap them in the body HTML
    $cBodyContent = $this->RenderTemplate('Idfix', array(
      'theme' => (isset($this->aConfig['theme']) ? $this->aConfig['theme'] : ''),
      'content' => $content,
      'navbar' => $navbar,
      'messages' => $messages,
      'javascript' => $this->GetSetClientTaskUrl(),
      ));
    echo $cBodyContent;
    //$this->IdfixDebug->Profiler(__method__, 'stop');
  }

  /**
   * 
   * @param char $cConfigName 
   *   Name of the configuration to show
   * @param char $cTableName 
   *   Name of the table
   * @param char $cFieldName 
   *   Name of the field
   * @param int $iObject
   *   Unique ID of the
   * @param char $cAction
   *   Action to perform
   */
  public function Render($cConfigName, $cTableName, $cFieldName, $iObject, $iParent, $cAction, $aParams = array()) {
    $this->cConfigName = $cConfigName;
    $this->cTableName = $cTableName;
    $this->cFieldName = $cFieldName;
    $this->cAction = ucfirst($this->ValidIdentifier($cAction));
    $this->iObject = intval($iObject);
    $this->iParent = intval($iParent);

    // Create a default cache key
    $cConfigCacheKey = $this->GetConfigCacheKey();
    $this->aConfig = $this->GetSetCache($cConfigCacheKey);

    // Check if we got one...
    if (!is_array($this->aConfig)) {
      // Ok, we need to create it from scratch ....
      $this->Event('GetConfig');
      // Time to to some checking for the correct tables
      // Do it here because we only need to to it once
      if (count($this->aConfig['tables']) > 0) {
        // Only create the table if there are entries in the
        // tables array. This way we do not create tables if
        // we accidently try to access non existing configs
        $this->IdfixStorage->check();
      }
      // And do not forget to store evertything in cache
      $this->GetSetCache($cConfigCacheKey, $this->aConfig);
    }

    // Call an event specifically meant to do setup tasks
    $this->Event('Init');


    // Create the output variable
    $output = '';

    // Create the Main EventName
    $cEventName = 'Action' . $this->cAction;
    // ... and call the main event
    $this->Event($cEventName, $output, $aParams);

    // Call an extra event for postprocessing
    $this->Event('Render', $output);


    //$this->IdfixDebug->Debug('Config', $this->aConfig);

    return $output;
  }


  /**
   * Wrapper for generating the navigation system
   * 
   * @return string Rendered NavigationBar
   */
  private function RenderNavBar() {
    $this->IdfixDebug->Profiler(__method__, 'start');
    // Create the empty Navigationbar structure
    // Brand = The leftmost, biogger name of the system
    // left = structure floated to the left
    // right = structure floated tio the right
    $aNavBar = array(
      'brand' => array(),
      'left' => array(),
      'right' => array());
    // Call the event system to fill it
    $this->Event('Navbar', $aNavBar);
    // Than render it to HTML
    $cNavBar = $this->RenderTemplate('IdfixNavbar', array('navbar' => $aNavBar));
    // Stop profiling
    $this->IdfixDebug->Profiler(__method__, 'stop');
    // Return the rendered navigation bar
    return $cNavBar;
  }

  /**
   * Do a nice redirect to another page while maintaining the
   * session and also the flash messages.
   * 
   * @param mixed $cUrl
   * @return void
   */
  public function Redirect($cUrl) {
    // Never redirect if in unittest mode!!
    //echo 'redirected to: ' . $cUrl;
    //return;
    //return;
    if (!$this->ev3->bTest) {
      session_write_close();
      header('location: ' . $cUrl);
      exit(0);
    }
  }
  /**
   * Isfix event handler for the navigation bar
   * 
   * @param mixed $data
   * @return void
   */
  public function Events3IdfixNavbar(&$data) {
    // First set the name of the application
    $data['brand']['title'] = $this->aConfig['title'];
    if ($this->Access(self::PERM_ACCESS_INFOPANEL)) {
      $data['brand']['href'] = $this->GetUrl('', '', '', null, null, 'info');
    }
    else {
      $data['brand']['href'] = '#';
    }

    $data['brand']['tooltip'] = $this->aConfig['description'];
    $data['brand']['icon'] = $this->GetIconHTML($this->aConfig);

    // Add a link for adding a new record
    $bAccess = $this->Access($this->cTableName . '_a');
    $bIsList = (boolean)(substr($this->cAction, 0, 4) == 'List');
    if (isset($this->aConfig['tables'][$this->cTableName]) and $bIsList and $bAccess) {
      $data['left'][''] = array(
        'title' => 'New ' . $this->aConfig['tables'][$this->cTableName]['title'],
        'tooltip' => $this->aConfig['tables'][$this->cTableName]['description'],
        'href' => $this->GetUrl($this->cConfigName, $this->cTableName, '', 0, $this->iParent, 'edit'),
        'icon' => $this->GetIconHTML('plus'),
        );
    }


    // Create a dropdown structure for showing all the tables in the system
    // Temporary code shows ALL the tables, not only top level
    $aDropdown = array();
    foreach ($this->aConfig['tables'] as $cTableName => $aTableConfig) {
      // Be sure we do not have child objects
      if ($this->TableIsTopLevel($cTableName) and $this->Access($cTableName . '_v')) {
        // Get all the available listviews and pick the default
        $aViews = $this->GetListViews($cTableName);
        $cListAction = $aViews['default']['action'];
        $cListIcon = $aViews['default']['icon'];

        $aDropdown[$cTableName] = array(
          'title' => isset($aTableConfig['title']) ? $aTableConfig['title'] : '',
          'href' => $this->GetUrl($this->cConfigName, $cTableName, '', 1, 0, $cListAction), // top level list
          'listicon' => $this->GetIconHTML($cListIcon),
          'tooltip' => isset($aTableConfig['description']) ? $aTableConfig['description'] : '',
          'icon' => $this->GetIconHTML($aTableConfig),
          'active' => ($cTableName == $this->cTableName),
          'type' => 'normal',
          );
      }
    }

    // List the main Tables in the system
    $data['left']['tables'] = array(
      'title' => 'Tables',
      'tooltip' => 'Select one of the top-level tables in the system.',
      'href' => '#',
      'dropdown' => $aDropdown,
      'icon' => $this->GetIconHTML('tasks'),
      );


    // Create a dropdown of avalable views
    $aViews = $this->GetListViews();
    if (count($aViews) > 1) {
      $aDropdown = array();
      foreach ($aViews as $aViewConfig) {
        $aDropdown[] = array(
          'title' => $aViewConfig['title'],
          'href' => $aViewConfig['url'],
          'tooltip' => '',
          'icon' => $this->GetIconHTML($aViewConfig['icon']),
          'active' => (boolean)(strtolower($this->cAction) == strtolower($aViewConfig['action'])),
          'type' => 'normal',
          );
      }

      // Check if one of the dropdowns is active, only than
      // we can switch views. At other actions/screens we do not
      // need to show this dropdown.
      $bShowViewChanger = false;
      foreach ($aDropdown as $aDropConfig) {
        if ($aDropConfig['active']) {
          $bShowViewChanger = true;
          break;
        }
      }
      // List The available views on the default table
      if ($bShowViewChanger) {
        $data['left']['views'] = array(
          'title' => 'Views',
          'tooltip' => 'Select an available view.',
          'href' => '#',
          'dropdown' => $aDropdown,
          'icon' => $this->GetIconHTML('eye-open'),
          );
      }
    }


    // Powered By Idfix
    $data['right']['system'] = array(
      'title' => 'Powered by Idfix',
      'tooltip' => 'Agile PHP Cloud Development Platform',
      'href' => $this->GetUrl($this->cConfigName, '', '', 0, 0, 'Controlpanel'), // top level list
      'icon' => '',
      );
  }

  public function Events3IdfixGetPermissions(&$aPerm) {
    $aPerm[self::PERM_ACCESS_INFOPANEL] = 'Access Information Panel';
  }

  /**
   * Check to see if we have a top level table
   * 
   * @param mixed $cTableName
   * @return boolean true if it is a top level table
   */
  private function TableIsTopLevel($cTableName) {
    $bRetval = true;

    if (is_array($this->aConfig['tables'])) {
      foreach ($this->aConfig['tables'] as $table_sub_name => $aTableConfig) {
        if (isset($aTableConfig['childs']) and is_array($aTableConfig['childs'])) {
          // Check if the tablename itself is in it's child list
          // If that's the case we have inheritance and the table must
          // be top level
          $is_inherit = in_array($table_sub_name, $aTableConfig['childs']);
          if (in_array($cTableName, $aTableConfig['childs']) and !$is_inherit) {
            $bRetval = false;
            break;
          }
        }
      }
    }

    return $bRetval;
  }

  /**
   * Generate an array of all the listviews we support, and also mark the default.
   * 
   * @param string $cTablename, otherwise the default table
   * @return array: list of all the supported listviews
   */
  public function GetListViews($cTablename = '') {
    static $cache = array();
    $cTablename = ($cTablename ? $cTablename : $this->cTableName);
    if (!isset($this->aConfig['tables'][$cTablename])) {
      return array();
    }

    // Check static cache
    if (isset($cache[$cTablename])) {
      return $cache[$cTablename];
    }

    $this->IdfixDebug->Profiler(__method__, 'start');


    $cDefault = 'list';
    $cValidViewTypes = ';;list;;date;;map;;';

    // Get a valid config
    $aTableConfig = $this->aConfig['tables'][$cTablename];

    // This is the standard list, it is always available
    $aReturn['list'] = array(
      'title' => 'List',
      'action' => 'list',
      'url' => $this->GetUrl('', $cTablename, '', 1, null, 'list'),
      'icon' => 'list-alt');

    // Check if we are going to make the datelist available
    $bDateViewSet = (boolean)(isset($aTableConfig['view']) and $aTableConfig['view'] == 'date');
    $bDateConfigSet = (boolean)(isset($aTableConfig['date']));
    $bDateFieldAvailable = false;
    foreach ($aTableConfig['fields'] as $cFieldName => $aFieldConfig) {
      if ($aFieldConfig['type'] == 'date') {
        $bDateFieldAvailable = true;
        break;
      }
    }
    if ($bDateViewSet or $bDateConfigSet or $bDateFieldAvailable) {
      $aReturn['date'] = array(
        'title' => 'Calendar',
        'action' => 'listdate',
        'url' => $this->GetUrl('', $cTablename, '', 0, null, 'listdate'),
        'icon' => 'calendar');
      // Make it the default in certain cases ....
      if ($bDateViewSet) {
        $cDefault = 'date';
      }
    }

    // Check if we are going to make the Google maps View available
    $bMapViewSet = (boolean)(isset($aTableConfig['view']) and $aTableConfig['view'] == 'map');
    $bMapConfigSet = (boolean)(isset($aTableConfig['map']));
    $bMapFieldAvailable = false;
    foreach ($aTableConfig['fields'] as $cFieldName => $aFieldConfig) {
      if ($aFieldConfig['type'] == 'map') {
        $bMapFieldAvailable = true;
        break;
      }
    }
    if ($bMapConfigSet or $bMapFieldAvailable or $bMapViewSet) {
      $aReturn['map'] = array(
        'title' => 'Map',
        'action' => 'map',
        'url' => $this->GetUrl('', $cTablename, '', 0, null, 'map'),
        'icon' => 'map-marker');
      // Make it the default in certain cases ....
      if ($bMapViewSet) {
        $cDefault = 'map';
      }
    }

    // Create a default by renaming the key to: default
    $aReturn['default'] = $aReturn[$cDefault];
    unset($aReturn[$cDefault]);


    // Set static cache
    $cache[$cTablename] = $aReturn;

    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $aReturn;
  }

  /**
   * Main event handling system of Idfix
   * Every events results in 3 events raised on the
   * framework level
   * 
   * @param string $cEventName
   * @param mixed &$xValue Most of the time a data strtucture
   * @return void The datastructure can be manipulated
   */
  public function Event($cEventName, &$xValue = '', $aParams = array()) {
    // Prefix all events with Idfix
    $cEventName = 'Idfix' . $cEventName;
    //$this->IdfixDebug->Profiler(__method__ . '::' . $cEventName, 'start');
    // Raise three events
    $this->ev3->Raise($cEventName . 'Before', $xValue, $aParams);
    $this->ev3->Raise($cEventName, $xValue, $aParams);
    $this->ev3->Raise($cEventName . 'After', $xValue, $aParams);
    // Stop profiling
    //$this->IdfixDebug->Profiler(__method__ . '::' . $cEventName, 'stop');
  }


  /**
   * Support functions
   * 
   */

  /**
   * Render an Idfix template
   * 
   * @param string $cTemplateName Name of the template without path and extention
   * @param array $aVars Template variables (@see Template Module)
   * @return string Rendered template
   */
  public function RenderTemplate($cTemplateName, $aVars = array()) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $cTemplateFile = dirname(__file__) . "/templates/{$cTemplateName}.php";
    $cReturn = $this->Template->Render($cTemplateFile, $aVars);
    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $cReturn;
  }

  /**
   * Give us a url to a Idfix page and use some intelligent default values
   * 
   * @param string $cConfigName
   * @param string $cTablename
   * @param string $cFieldName
   * @param integer $iObject
   * @param integer $iParent
   * @param string $cAction
   * @param string $cAttributes Optiobnal exctra info to append to the querystring
   * @return
   */
  public function GetUrl($cConfigName = '', $cTablename = '', $cFieldName = '', $iObject = null, $iParent = null, $cAction = '', $aAttributes = array()) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $cConfigName = $cConfigName ? $cConfigName : $this->cConfigName;
    $cTablename = $cTablename ? $cTablename : $this->cTableName;
    $cFieldName = $cFieldName ? $cFieldName : $this->cFieldName;
    $iObject = !is_null($iObject) ? $iObject : $this->iObject;
    $iParent = !is_null($iParent) ? $iParent : $this->iParent;
    $cAction = $cAction ? $cAction : $this->cAction;

    // Create an array of querystring parameters
    $cPath = "/{$cConfigName}/{$cTablename}/{$cFieldName}/{$iObject}/{$iParent}/{$cAction}";

    // Now let other modules add default values to it if needed
    // See the sort module for information
    $this->Event('GetUrl', $aAttributes);

    // Create the concatenated querystring
    $cQueryString = http_build_query($aAttributes);
    // But decode it, because there might be variables in there for postprocessing!!!
    $cQueryString = urldecode($cQueryString);
    //$cQueryString = implode('&',$aAttributes);

    $cUrl = $this->ev3->BasePathUrl . $cPath . '?' . $cQueryString;


    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $cUrl;
  }

  /**
   * Create a nice identifier that's stripped from all
   * of the bad characters and leaves us only with
   * - lowercase
   * - digits
   * - alpha
   * - underscore
   * 
   * @param string $cKey
   * @return string Cleaned op $cKey
   */
  public function ValidIdentifier($cKey) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $cKey = strtolower($cKey);
    $blacklist = str_replace(str_split('abcdefghijklmnopqrstuvwxyz_1234567890'), '', $cKey);
    if ($blacklist) {
      $cKey = str_replace(str_split($blacklist), '_', $cKey);
    }
    if (is_numeric(substr($cKey, 0, 1))) {
      $cKey = '_' . $cKey;
    }
    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $cKey;
  }


  /**
   * Check access restrictions
   * 
   * @param mixed $cPermission
   * @return
   */
  public function Access($cPermission) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    //$args = func_get_args();
    $bAccess = true;
    // Put all the values in the array
    $aPack = compact('bAccess', 'cPermission');
    // And send them to the event handler
    $this->Event('Access', $aPack);
    // Now only extract the access value
    $bAccess = (boolean)$aPack['bAccess'];
    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $bAccess;

  }


  /**
   * Cleanup any string for output
   * 
   * 
   * @param mixed $cText
   * @return
   */
  public function CleanOutputString($cText) {
    return htmlspecialchars($cText, ENT_QUOTES, 'UTF-8');
  }


  /**
   * Idfix::GetIconHTML()
   * This method is nessecary because we can have different types of
   * iconlibs.
   * 1. Built in bootstrap icons
   * 2. A remote library
   * 3. Local file library
   * 
   * @param mixed $cIcon Name of the icon
   * @return string Full HTML for display purposes
   */
  public function GetIconHTML($cIcon) {
    // Special case if we send in a configuration structure
    if (is_array($cIcon)) {
      if (isset($cIcon['icon'])) {
        $cIcon = $cIcon['icon'];
      }
      else {
        $cIcon = '';
      }
    }

    // Default iconsize
    $cIconSize = 'height="16"';
    // If we need to display the application icon we use a special size
    if ($cIcon == $this->aConfig['icon']) {
      $cIconSize = 'height="' . $this->aConfig['iconsize'] . '"';
    }

    // Custom icon from somewhere on the internet
    if (substr($cIcon, 0, 4) == 'http') {
      return "<img align=\"absmiddle\" {$cIconSize} src=\"{$cIcon}\">&nbsp;";
    }
    // Icon from thwe Font Awsome library
    elseif (substr($cIcon, 0, 3) == 'fa-') {
      return "<i class=\"fa {$cIcon}\"></i>&nbsp;";
    }
    elseif ($cIcon and strtolower($this->aConfig['iconlib']) == 'bootstrap') {
      return "<span class=\"glyphicon glyphicon-{$cIcon}\"></span>&nbsp;";
    }
    elseif ($cIcon) {
      $cIcon = $this->aConfig['iconlib'] . '/' . $cIcon;
      return "<img align=\"absmiddle\" {$cIconSize} src=\"{$cIcon}\">&nbsp;";
    }

    return '';
  }

  /**
   * PostprocesConfig()

   * Postprocess a (part of a) configuration array for dynamic values
   *
   * Check for:
   * 1. Callbacks (with optional parameters)
   * 2. variables
   *
   * Variables are names surrounded by an % like '%ParenID%'
   * These are substituted with the values from the second
   * parameter to this function: $aRecord
   *
   * @param
   *   mixed $aConfig
   * @param
   *   mixed $aRecord
   * @return
   *   Processed configuration
   */
  public function PostprocesConfig($aConfig, $aRecord = array()) {
    $this->IdfixDebug->Profiler(__method__, 'start');

    // S H O R T C U T
    // If it is a field, the configuration preprocessor already analyzed
    // if this field needs postprocessing. In that case we can skip this
    // expensive effort.....
    if (isset($aConfig['_NoPP']) and $aConfig['_NoPP']) {
      $this->IdfixDebug->Profiler(__method__, 'stop');
      return $aConfig;
    }

    if (is_array($aConfig)) {
      foreach ($aConfig as $cElementName => &$aConfig_element) {

        // 1. Get dynamic display values, option elements!!!!
        if ((is_string($aConfig_element) and (stripos($aConfig_element, '%%') !== false))) {
          $aConfig_element = $this->DynamicDisplayValues($aConfig_element, $aRecord);
        }

        // 2. Get normal dynamic values to parse?
        if (is_string($aConfig_element) and (stripos($aConfig_element, '%') !== false)) {
          $aConfig_element = $this->DynamicValues($aConfig_element, $aRecord);
        }
        // 3. Special Idfix options array
        elseif ($cElementName == 'options' and isset($aConfig_element['table'])) {
          $aConfig_element = $this->Check4IdfixObjects($aConfig_element);
        }
        // 4. Plain array? Recursive action
        elseif (is_array($aConfig_element)) {
          $aConfig_element = $this->PostprocesConfig($aConfig_element, $aRecord);
        }
      }
    }

    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $aConfig;
  }

  /**
   * IdfixFieldsInputOptionsBase::Check4IdfixObjects()
   * 
   * Options supported:
   *  -table=<tablename>
   *  
   *   -display=%Name% (%%SubTypeID%%)
   *  [-display=<Single FieldName>]  faster option
   *   -display=   <- defaults to Name
   * 
   *   -order=<Valid Fieldname From Table>
   *   -limit=<number>  maximum number of entries in the list
   *   -where=<valid where clause>
   * 
   * @param mixed $aOptions
   * @return void
   */
  private function Check4IdfixObjects($aOptions) {
    // Do some static caching.....
    static $aCache = array();
    // Caching on only the table name could be insufficient...
    $cCacheKey = md5(serialize($aOptions));
    // Check the cache
    if (isset($aCache[$cCacheKey])) {
      // Stop static caching to show the impact on performance
      //return $aCache[$cCacheKey];
    }

    $this->IdfixDebug->Profiler(__method__, 'start');

    $aReturn = $aOptions;
    // Really simple, check if this is a special case optionas array
    // with parameters
    if (isset($aOptions['table'])) {
      $cTableName = $aOptions['table'];
      if (isset($this->Idfix->aConfig['tables'][$cTableName])) {
        //print_r($aOptions);
        $aTableConfig = $this->Idfix->aConfig['tables'][$cTableName];
        // Ok, we're sure we got a valid table
        $aReturn = array();
        // Now get a valid trail, but also add 0 for top level objects
        $aTrail = $this->Trail($this->Idfix->iParent);
        $aTrail[0] = 0;
        // This variant is needed for the SQL query
        $cTrail = implode(',', array_keys($aTrail));
        $cOrder = (isset($aOptions['order']) ? $aOptions['order'] : '');
        $iLimit = (integer)(isset($aOptions['limit']) ? $aOptions['limit'] : 0);
        $aWhere = (isset($aOptions['where']) ? array($aOptions['where']) : array());
        $aRecords = $this->IdfixStorage->LoadAllRecords($aTableConfig['id'], $cTrail, $cOrder, $aWhere, $iLimit);
        // Get the display mask and see if it needs postprocessing, if not it should be a fieldname
        $aOptionsToProces['display'] = $aOptions['display'];
        $bDisplayNeesdsPP = (strpos($aOptionsToProces['display'], '%') !== false);
        // Now we have a data set, loop through it and get our options
        // Indexed ofcourse by the MainID of the Idfix object
        foreach ($aRecords as $iMainId => $aRow) {
          $cDisplayItem = '';
          if ($bDisplayNeesdsPP) {
            $OptionsProcessed = $this->Idfix->PostprocesConfig($aOptionsToProces, $aRow);
            $cDisplayItem = $OptionsProcessed['display'];
          }
          else {
            // It's just a fieldname :-)
            $cFieldName = $aOptionsToProces['display'];
            if (isset($aRow[$cFieldName])) {
              $cDisplayItem = $aRow[$cFieldName];
            }
          }
          $aReturn[$iMainId] = $cDisplayItem;
        }
      }
    }
    // Store in the static cache
    $aCache[$cCacheKey] = $aReturn;
    // Stop profiling
    $this->IdfixDebug->Profiler(__method__, 'stop');

    return $aReturn;
  }

  /**
   * Idfix::DynamicDisplayValues()
   * 
   * Use the double procents with care. There is a lot of processing involved here :-(
   * 
   * @param mixed $aHaystack
   * @param mixed $aValues
   * @return void
   */
  private function DynamicDisplayValues($aHaystack, $aValues) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    if (is_array($aValues)) {
      // We need to know the table configuration first
      $aTableConfig = $this->TableConfigById($aValues['TypeID']);
      // Now process all the values
      foreach ($aValues as $cKey => $xValue) {
        $search = '%%' . $cKey . '%%';
        if (strpos($aHaystack, $search) !== false) {
          // Ok, now we know that this key is a fieldname and should be
          // replaced by the displayvalue, but first we need to get
          // the full field configuration
          if (isset($aTableConfig['fields'][$cKey])) {
            // The fieldconfiguration
            $aFieldConfig = $aTableConfig['fields'][$cKey];
            // Set the value to prosess
            $aFieldConfig['__RawValue'] = $xValue;
            // Call the weventhandler on the field
            $this->Idfix->Event('DisplayField', $aFieldConfig);
            // Set the value back in the variable
            $xValue = $aFieldConfig['__DisplayValue'];
            // And do a search and replace on the element
            $aHaystack = str_replace($search, $xValue, $aHaystack);
          }
        }
      }
    }
    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $aHaystack;
  }

  /**
   * $this->DynamicValues()
   * Only called once from the above method :-)
   *
   * @param
   *   mixed $aHaystack
   * @param
   *   mixed $aValues
   * @return
   *   Processed data structure
   */
  private function DynamicValues($aHaystack, $aValues) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    if (is_array($aValues)) {
      foreach ($aValues as $cKey => $xValue) {
        $search = '%' . $cKey . '%';
        if (strpos($aHaystack, $search) !== false) {
          $aHaystack = str_replace($search, $xValue, $aHaystack);
        }
      }
    }
    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $aHaystack;
  }

  /**
   * Set a message to show on the next page
   * 
   * @param string $cMessage
   * @param string $cType = (success,info,warning,danger)
   * @return string rendered messages if no parameters are sent
   */
  public function FlashMessage($cMessage = null, $cType = 'success') {
    if (is_null($cMessage)) {
      $cMessages = '';
      // Render the messages
      if (isset($_SESSION[__method__]) and is_array($_SESSION[__method__])) {
        foreach ($_SESSION[__method__] as $cType => $aMessages) {
          $cMessages .= $this->RenderTemplate('FlashMessages', compact('cType', 'aMessages'));
        }
      }
      // Clear the mesaage queu
      unset($_SESSION[__method__]);
      return $cMessages;
    }
    else {
      // Set new message
      $_SESSION[__method__][$cType][] = $cMessage;
    }
  }

  /**
   * Sometimes we just need to know what was the last page showed
   * in the list.
   * Fort example if we start editing a value. After that we need to go to the 
   * correct page.
   * This is a helper function for that sitaution
   * Call it without parameters to store the last active page.
   * Give it a tablename and it returns the last known page.
   * 
   * @param string $cTableName
   * @return integer PageNumber for the specified table. defaults to 1.
   */
  public function GetSetLastListPage($cTableName = '') {
    $cUrl = '';
    $cSessionKey = '__Idfix__LastListPage_';
    // Set Value
    if (!$cTableName) {
      $cUrl = $this->GetUrl();
      $_SESSION[$cSessionKey] = $cUrl;
    }
    // Get value
    else {
      if (isset($_SESSION[$cSessionKey])) {
        $cUrl = $_SESSION[$cSessionKey];
      }
    }

    return $cUrl;
  }

  /**
   * Workhorse function for caching all kind of things.
   * 
   * We are using session based caching because:
   * 1. It is as fast as static caching
   * 2. Storing and retrieving is done just once when the session
   *   is started and when the the PHP system is closed down
   * 3. By default we have file based sessions and they are read
   *    by PHP itself (C-code) and operating system, mostly LINUX.
   *    Bottomline: as fast as possible
   * 4. We can skip a lot of expensive MySql queries that are normally
   *    done every call to the server.
   * 
   * I know that we are impacting performance by putting the profiler
   * statements in here. But we really want to know the impact on perfomance
   * this cache has. So it is a bit of a tradeoff.
   * 
   * But note that if the profiling system is shut down, there is only a function
   * call performed with no code whatsoever.
   * 
   * @param string $cKey
   * @param mixed $xValue
   * @return mixed Cached $xValue
   */
  public function GetSetCache($cKey, $xValue = null, $bDelete = false) {
    // Start profiling
    $this->IdfixDebug->Profiler(__method__, 'start');

    // Only use the cache if it is configurated
    if ($this->IdfixConfigCache or $bDelete) {

      // Prefix the key
      $cKey = 'configcache_' . $cKey;

      // Remove the key form the cache
      if ($bDelete) {
        $this->ev3->CacheDelete($cKey);
        $this->log('Cache Deleteded for ' . $cKey);
      }
      // Try to get a value from the cache
      elseif (is_null($xValue)) {
        $xValue = $this->ev3->CacheGet($cKey);
      }
      // OK, we must set the cache
      else {
        $this->ev3->CacheSet($cKey, $xValue);
      }
    }

    // Stop profiling
    $this->IdfixDebug->Profiler(__method__, 'stop');

    return $xValue;
  }

  /**
   * Idfix::GetConfigCacheKey()
   * 
   * @return
   */
  private function GetConfigCacheKey($cConfigName = '') {
    // Create a default cache key
    $cConfigCacheKey = $this->cConfigName;
    if ($cConfigName) {
      $cConfigCacheKey = $cConfigName;
    }
    // Call an event to make the cachingname more specific
    $this->Event('ConfigCache', $cConfigCacheKey);
    // And return it
    return $cConfigCacheKey;
  }

  /**
   * Call this from outside the module to clear the cache
   * 
   * @return void
   */
  public function ClearConfigCache($cConfigName = '') {
    $cKey = $this->GetConfigCacheKey($cConfigName);
    $this->GetSetCache($cKey, null, true);
  }

  /**
   * Trail()
   *
   * @param
   *   integer $iMainId
   * @return
   *   Array with the current trail
   */
  public function Trail($iMainId) {
    // return cached trail if set
    static $aStaticCache = array();
    if (isset($aStaticCache[$iMainId])) {
      return $aStaticCache[$iMainId];
    }

    $aTrail = array();

    $next_id = $iMainId;
    while ($next_id) {
      $aRecord = $this->IdfixStorage->LoadRecord($next_id);
      $aTrail[$aRecord['MainID']] = $aRecord['TypeID'];
      $next_id = $aRecord['ParentID'];
    }

    // Store the trail for later use
    $aStaticCache[$iMainId] = $aTrail;

    return $aTrail;

  }

  /**
   * Idfix::BreadCrumbs()
   * 
   * @param mixed $iMainId
   * @return string Fully rendered OL list for showing breadcrumbs from the current MainID
   */
  public function BreadCrumbs($iMainId) {
    $aBaseTrail = $this->Trail($iMainId);
    $aTrail = array();


    // New functionality based on the API trail function
    foreach ($aBaseTrail as $iTrailMainId => $iTrailTypeId) {
      $aRecord = $this->IdfixStorage->LoadRecord($iTrailMainId);
      $aTrail[] = $this->BreadCrumbItem($aRecord, $iTrailTypeId);
    }

    $cItems = implode('', array_reverse($aTrail));

    $cReturn = '';
    if ($cItems) {
      $cReturn = "<ol class=\"breadcrumb\">{$cItems}</ol>";
    }

    return $cReturn;
  }

  /**
   * Idfix::BreadCrumbItem()
   * 
   * @param array $aRecord Record from the idfix table
   * @param integer $iTypeId Unique identifier of the table
   * @return string List item for the breadcrumb trail
   */
  private function BreadCrumbItem($aRecord, $iTypeId) {
    $aConfig = $this->TableConfigById($iTypeId);
    $aConfig = $this->PostprocesConfig($aConfig, $aRecord);
    $cTableName = $aConfig['_name'];

    if (isset($aConfig['trail']) and $aConfig['trail']) {
      $cTitle = $aConfig['trail'];
    }
    else {
      $cTitle = $aConfig['title'] . ' ' . $aRecord['MainID'];
    }

    // Get the correct default view by tablename
    $aViews = $this->GetListViews($cTableName);
    $cDefaultAction = $aViews['default']['action'];

    $cUrl = $this->GetUrl('', $cTableName, '', 0, $aRecord['ParentID'], $cDefaultAction);
    $cReturn = "<li><a href=\"{$cUrl}\">{$cTitle}</a></li>";

    return $cReturn;
  }


  /**
   * Idfix::TableConfigById()
   * 
   * @param integer $iTypeId
   * @return array Table Configuration
   */
  private function TableConfigById($iTypeId) {
    static $aStaticCache = array();
    if (isset($aStaticCache[$iTypeId])) {
      return $aStaticCache[$iTypeId];
    }
    //$this->IdfixDebug->Profiler(__method__, 'start');

    $cReturn = array();
    $aConfig = $this->aConfig;
    foreach ($aConfig['tables'] as $cTableName => $aTableConfig) {
      if ($aTableConfig['id'] == $iTypeId) {
        $cReturn = $aTableConfig;
        break;
      }
    }

    $aStaticCache[$iTypeId] = $cReturn;
    //$this->IdfixDebug->Profiler(__method__, 'stop');
    return $cReturn;
  }

  public function Events3IdfixActionInfo(&$output) {
    // Main Output Array
    $aTables = array();
    // Agregated Data
    $cTableName = $this->aConfig['tablespace'];
    $cSql = "SELECT TypeID, count(*) as count, count(distinct ParentID) as parents, count(distinct UidCreate) as creators, Min(TSCreate) as ts_first, Max(TSCreate) as ts_last, Max(TSChange) as ts_change FROM {$cTableName} GROUP BY TypeID WITH ROLLUP";
    $aData = $this->Database->DataQuery($cSql);
    // Create infopanel for each aggregated data item
    // because of the ROLLUP we also have a summary!!
    foreach ($aData as $aRow) {
      $id = $aRow['TypeID'];
      $aTableConfig = $this->TableConfigById($id);

      $bIsSummary = is_null($id);
      $bInfoInConfig = isset($aTableConfig['title']);

      if (!$bIsSummary and !$bInfoInConfig) {
        continue;
      }

      // No TypeID = NULL => It is the summary
      $cClass = 'panel-default';
      if ($bIsSummary) {
        $aTableConfig['title'] = 'Summary';
        $aTableConfig['description'] = 'Information about <strong>all</strong> the records in this tablespace.';
        $cClass = 'panel-success';
      }

      // Items for the template
      $aTables[$id] = array(
        'data' => $this->RenderTemplate('ConfigInfoPanel', compact('aRow')),
        'icon' => $this->GetIconHTML($aTableConfig),
        'title' => $aTableConfig['title'],
        'description' => $aTableConfig['description'],
        'class' => $cClass,
        );
    }
    $output = $this->RenderTemplate('ConfigInfo', compact('aTables'));
  }

  /**
   * Sometimes we need to do some background processing.
   * 
   * But if we do it during the rendering process for the client,
   * it just takes too long.
   * 
   * We have a task system in Google App Engine. And of course
   * we have a CURL library.
   * 
   * But it's all a lot of work. And on top of that, we need to be
   * in the current session!!!!
   * 
   * There's a very simple solution. Create a url and let the client
   * trigger it for us. That way we know we're in the session and it's very
   * easy to set up.
   * 
   * Jquery already has a nice $.get() method for that.
   * 
   * This function is a little pushqueu where we can set a couple of url's
   * Than they are rendered as javascript en send to the client.
   *  
   * @param string $cUrl
   * @return void
   */
  public function GetSetClientTaskUrl($cUrl = '') {
    if ($cUrl) {
      $_SESSION[__method__][] = $cUrl;
    }
    else {
      $cJavaScript = '';
      // Render javascript
      if (isset($_SESSION[__method__]) and is_array($_SESSION[__method__]) and count($_SESSION[__method__]) > 0) {
        foreach ($_SESSION[__method__] as $cUrl) {
          $cJavaScript .= "$.get(\"{$cUrl}\");\r\n";
        }
        $cJavaScript = "<script type=\"text/javascript\">\r\n{$cJavaScript}</script>";
        //unset($_SESSION[__method__]);
      }
      return $cJavaScript;
    }
  }

}
