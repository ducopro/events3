<?php

//require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
//use google\appengine\api\cloud_storage\CloudStorageTools;

/**
 * This module makes Idfix behave like an OTAP Environment
 * For every environm,ent we have a
 * - Configuration File
 * - Public Up- and Download directory
 * - Database table
 * 
 * There is also a user interface controlpanel for checking and controling
 * the environments.
 * 
 * To disable OTAP functionality just disable this module. Simple :-)
 * 
 * This Module uses the following file structure:
 * 
 * Events3::PublicPath/
 *   otap/
 *     dev/
 *       config/
 *          myconfig.idfix   The main idfix configuration file
 *          myconfig2.idfix
 *          myconfig3(ect.).idfix   
 *       files/
 *         myconfig/
 *         myconfig2/
 *         myconfig3/
 *     test/
 *       config/
 *       files/
 *     accept/
 *       config/
 *       files/
 *     prod/  
 *       config/
 *       files/
 * 
 * It does this by Managing the 
 * - IdfixParse module to look in the right directory
 * - Updating the table space froperty from the config to point to the right environment
 * - Adding a reference to the right upload directory
 * - Creating the directory structure 
 * - Always adding the right environment to the GET-string of the url
 * 
 * *************************************
 * *** B A C K U P   system          ***
 * *************************************
 * The backup system is integrated in the OTAP module
 * because of the dependencies on the functionality in this module.
 * To keep the code as clean as possible the backup code is present
 * in it's own section in the module file.
 * Additionally there is an include file for all the routines actually
 * needed for backing up and restoring to the filesystem and database.
 * 
 */
class IdfixOtap extends Events3Module {

  // Backup actions
  const BACKUP_ACTION_NEW = 1;
  const BACKUP_ACTION_RESTORE = 2;
  const BACKUP_ACTION_DELETE = 3;
  const BACKUP_ACTION_UPLOAD = 4;
  const BACKUP_ACTION_DOWNLOAD = 5;

  // Permissions
  const PERM_ACCESS_CONTROLPANEL = 'otap_access';
  const PERM_DELETE_CONFIG = 'otap_del_config';
  // List of environment strings
  const ENV_DEV = 'dev';
  const ENV_TEST = 'test';
  const ENV_ACC = 'accept';
  const ENV_PROD = 'prod';

  // List of environments in correct order
  public $aEnvList = array(
    self::ENV_DEV => 1,
    self::ENV_TEST => 2,
    self::ENV_ACC => 3,
    self::ENV_PROD => 4,
    );
  private $aEnvDescription = array(
    self::ENV_DEV => 'Development',
    self::ENV_TEST => 'Test',
    self::ENV_ACC => 'Acceptance',
    self::ENV_PROD => 'Production',
    );

  // The GET variable to look for
  const GET_OTAP = 'otap';

  // Current and default environment if nothing is found in URL
  public $cCurrentEnvironment = IdfixOtap::ENV_PROD;

  /**
   * Simple getter for the environment description
   * 
   * @param mixed $cEnv
   * @return
   */
  public function GetEnvironmentAsText($cEnv = null) {
    if (is_null($cEnv)) {
      $cEnv = $this->cCurrentEnvironment;
    }
    return $this->aEnvDescription[$cEnv];
  }

  /**
   * Read the correct environment from the url
   * 
   * @return void
   */
  public function __construct() {
    parent::__construct();
    // Is there an OTAP direction in the url??
    if (isset($_GET[IdfixOtap::GET_OTAP])) {
      // Get it
      $cEnv = $_GET[IdfixOtap::GET_OTAP];
      // .. and check if it is correct
      if (IdfixOtap::ENV_DEV == $cEnv or IdfixOtap::ENV_TEST == $cEnv or IdfixOtap::ENV_ACC == $cEnv or IdfixOtap::ENV_PROD == $cEnv) {
        // Set the current environment
        $this->cCurrentEnvironment = $cEnv;
      }
    }
  }

  public function Events3IdfixGetPermissions(&$aPerm) {
    $aPerm[self::PERM_ACCESS_CONTROLPANEL] = 'Access DTAP Controlpanel';
    $aPerm[self::PERM_DELETE_CONFIG] = 'Delete Idfix Configuration';
  }

  /**
   * Return complete list of found configurations
   * needed for building the accordeon on the login page
   * to choose a configuration top login to.
   * 
   * @todo Parse the configurations for title, icon and description.
   * 
   * @return
   */
  public function GetActiveConfigList() {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $aReturn = array();

    $xCache = $this->ev3->CacheGet(__method__);
    if (is_array($xCache)) {
      return $xCache;
    }

    // GAE
    //$aModuleList = glob($this->GetConfigDir(self::ENV_DEV) . '/*.idfix');
    $aModuleList = $this->GetCachedConfigList();

    //$this->IdfixDebug->Debug('Modules', $aModuleList);

    foreach ($aModuleList as $cFileName) {
      $cConfigName = basename($cFileName, '.idfix');
      $bConfigActive = ($this->Idfix->cConfigName == $cConfigName);

      // Get the configuratio title, description and icon defaults
      $cConfigTitle = $cConfigName;
      $cConfigDescription = '';
      $cConfigIcon = '';

      $aEnv = array();
      foreach ($this->aEnvList as $cEnv => $iEnv) {
        $cConfigFileName = $this->GetConfigFileName($cEnv, $cConfigName);
        $bEnvActive = (($this->cCurrentEnvironment == $cEnv) and $bConfigActive);
        $bConfigFilePresent = file_exists($cConfigFileName);
        $aEnv[$cEnv] = array(
          'title' => $this->aEnvDescription[$cEnv],
          'active' => $bEnvActive,
          'url' => $this->Idfix->GetUrl($cConfigName, '', '', 0, 0, 'Loginform', array('otap' => $cEnv)),
          'found' => $bConfigFilePresent,
          );

        // Now check if we need to load this config to determine the title, description and icon
        if (!$cConfigDescription and $bConfigFilePresent) {
          // Do a quick low level parse of the configfile
          $aConfig = $this->IdfixParse->Parse($cConfigFileName);
          $cConfigTitle = (isset($aConfig['title']) ? $aConfig['title'] : '');
          $cConfigDescription = (isset($aConfig['description']) ? $aConfig['description'] : '');
          $cConfigIcon = $this->Idfix->GetIconHTML($aConfig);
        }
      }

      $aReturn[$cConfigName] = array(
        'env' => $aEnv,
        'active' => $bConfigActive,
        'title' => $cConfigTitle,
        'description' => $cConfigDescription,
        'icon' => $cConfigIcon,
        );
    }

    // Cache it
    $this->ev3->CacheSet(__method__, $aReturn);

    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $aReturn;
  }

  public function Events3IdfixNavbarAfter(&$data) {
    // If the user module is enabled and we are running in superuser mode
    // we need to show all the available configurations and links to the controlpanel
    if ($oUserModule = $this->IdfixUser) {
      if ($oUserModule->IsSuperUser()) {
        $aDropdown = array();

        // GAE
        // $aModuleList = glob($this->GetConfigDir(self::ENV_DEV) . '/*.idfix');
        $aModuleList = $this->GetCachedConfigList();

        foreach ($aModuleList as $cFileName) {
          $cConfigName = basename($cFileName, '.idfix');
          $bActive = ($this->Idfix->cConfigName == $cConfigName);
          $aDropdown[$cConfigName] = array(
            'title' => $cConfigName,
            'href' => $this->Idfix->GetUrl($cConfigName, '', '', 0, 0, 'Controlpanel'),
            'tooltip' => $cConfigName,
            'icon' => $bActive ? $this->Idfix->GetIconHTML('ok') : '',
            'active' => $bActive,
            'type' => 'normal',
            );
        }
        $data['right']['configs'] = array(
          'title' => 'Configurations',
          'tooltip' => 'Select one of the configurations and show the control panel.',
          'href' => '#',
          'dropdown' => $aDropdown,
          'icon' => $this->Idfix->GetIconHTML('cloud'),
          );
      }
    }
    // Nothing else to show in production mode
    if ($this->cCurrentEnvironment == self::ENV_PROD) {
      return;
    }

    $data['right']['environment'] = array(
      'title' => "<span class=\"badge\">{$this->cCurrentEnvironment}</span>",
      'tooltip' => 'Agile PHP Cloud Development Platform',
      'href' => $this->Idfix->GetUrl($this->Idfix->cConfigName, '', '', 0, 0, 'Controlpanel'), // top level list
      'icon' => '',
      );

    // Add the save button to the toolbar
    if ($this->Idfix->cAction == 'Editconfig') {
      $cFile = $this->GetConfigFileName(self::ENV_DEV, $this->Idfix->cConfigName);
      $cUrl = $this->Idfix->GetUrl('', '', '', 0, 0, 'Saveconfig');
      $cIcon = $this->Idfix->GetIconHTML('save');
      $cButton = "<a href=\"#\" id= \"save-config\"  accesskey=\"s\" role=\"button\">{$cIcon} Save configuration to server (ALT+S)</a>";

      $data['custom']['savebutton'] = $cButton;
    }
  }

  /**
   * Event handler to add the OTAP key to the querystring
   * 
   * @param mixed $aParams
   * @return void
   */
  public function Events3IdfixGetUrl(&$aParams) {
    // Maybe we constructed the UIRL manualy
    // In that case we might have specified an OTAP environment
    if (!isset($aParams[self::GET_OTAP])) {
      $aParams[self::GET_OTAP] = $this->cCurrentEnvironment;
    }
  }

  /**
   * Implement a hook in the IdfixParse module to give us a different place to look for the
   * configuration file.
   * 
   * But what to do if it does not exist yet????
   * We make a special case for the adminsitrator to create an
   * empty configuration:
   * /mynewconfigname?create
   * 
   * No extra precautions are needed because only an empty config
   * file is created and the accompanying datafile
   * 
   * @param mixed $aData
   * @return void
   */
  public function Events3IdfixGetConfigFileName(&$aData) {
    // Set it in the return package
    $cConfigName = $aData['cConfigName'];
    $aData['cFileName'] = $this->GetConfigFileName($this->cCurrentEnvironment, $cConfigName);

    if (isset($_GET['create']) and $this->cCurrentEnvironment == self::ENV_DEV) {
      $this->CreateEmptyConfigInDev($cConfigName);
    }
    //print_r($aData);
    //echo $cConfigFile;
  }

  public function CreateEmptyConfigInDev($cConfigName) {
    $cFileName = $this->GetConfigFileName(self::ENV_DEV, $cConfigName);
    if (!file_exists($cFileName)) {
      file_put_contents($cFileName, "-title={$cConfigName}\n#tables");
      $this->Idfix->FlashMessage('Created empty DEV configuration: ' . $cFileName);
      // Do not forget to reset the configlist cache
      $this->GetCachedConfigList(true);
    }
  }

  /**
   * Add the environment to the key we use for caching 
   * the configurations
   * 
   * @param mixed $cConfigCacheKey
   * @return void
   */
  public function Events3IdfixConfigCache(&$cConfigCacheKey) {
    $cConfigCacheKey .= $this->cCurrentEnvironment;
  }
  /**
   * This hook is called by the IdfixParse module directly after
   * reading the configuration from disk.
   * We use it to set a tablespace and filespace specific for
   * this otap environment.
   * 
   * @param mixed $aConfig
   * @return void
   */
  public function Events3IdfixAfterParse(&$aConfig) {
    $cCurrentConfig = $this->Idfix->cConfigName;
    $aConfig['tablespace'] = $this->GetTableSpaceName($this->cCurrentEnvironment, $cCurrentConfig);
    $aConfig['filespace'] = $this->GetFilesDirConfig($this->cCurrentEnvironment, $cCurrentConfig);
  }

  public function Events3IdfixActionRemoveconfig(&$output) {
    if ($this->Idfix->Access(self::PERM_DELETE_CONFIG)) {
      // Everything we need :-)
      $cConfigName = $this->Idfix->cConfigName;
      $cEnv = $this->Idfix->cTableName;

      $this->DeleteConfigFile($cEnv, $cConfigName);
      $this->DeleteFileSystem($cEnv, $cConfigName);
      $this->DeleteTableSpace($cEnv, $cConfigName);
    }

    $this->RedirectToControlPanel();
  }

  public function Events3IdfixActionEditconfig(&$output) {
    if ($this->Idfix->Access(self::PERM_ACCESS_CONTROLPANEL)) {
      $cFile = $this->GetConfigFileName(self::ENV_DEV, $this->Idfix->cConfigName);
      $cUrl = $this->Idfix->GetUrl('', '', '', 0, 0, 'Saveconfig');
      $cIcon = $this->Idfix->GetIconHTML('save');
      $cButton = "<a href=\"#\" id= \"save-config\" class=\"btn btn-primary btn-block\" role=\"button\">{$cIcon} Save configuration to server</a>";

      $cFileContent = (string )trim(@file_get_contents($cFile));
      $output = $this->RenderTemplate('EditConfig', compact('cFileContent', 'cUrl', 'cButton'));
    }
  }

  /**
   * This is the ajax handler for saving the config file
   * 
   * @param mixed $output
   * @return void
   */
  public function Events3IdfixActionSaveconfig(&$output) {
    if ($this->IdfixUser and $this->Idfix->Access(self::PERM_ACCESS_CONTROLPANEL)) {
      if ($this->IdfixUser->IsSuperUser() or $this->IdfixUser->IsAdministrator()) {
        $config_contents = trim($_POST['config']);
        if ($config_contents and stripos($config_contents, '#tables')) {
          $cFile = $this->GetConfigFileName(self::ENV_DEV, $this->Idfix->cConfigName);
          file_put_contents($cFile, $config_contents);
          // Delete the parsed config from memcache
          $this->Idfix->ClearConfigCache();
        }
      }
    }
  }

  /**
   * Be Carefull!!!!
   * 
   * This method removes all files and datatables for 
   * this configuration.
   * Currently only called from the backoffice.
   * 
   * @param mixed $cConfigName
   * @return void
   */
  public function DeleteFullConfig($cConfigName) {
    foreach ($this->aEnvList as $cEnv => $cEnvOrder) {
      // Remove all traces
      $this->DeleteConfigFile($cEnv, $cConfigName);
      $this->DeleteBackupSystem($cEnv, $cConfigName);
      $this->DeleteFileSystem($cEnv, $cConfigName);
      $this->DeleteTableSpace($cEnv, $cConfigName);
      // And clear the cache also
      $this->ResetConfigCache($cEnv, $cConfigName);
    }
  }
  /**
   * Remove the configuration file from the filesystem
   * 
   * @param mixed $cEnv
   * @param mixed $cConfigName
   * @return void
   */
  private function DeleteConfigFile($cEnv, $cConfigName) {
    $cConfigFile = $this->GetConfigFileName($cEnv, $cConfigName);
    if (file_exists($cConfigFile)) {
      $this->log('Removed: ' . $cConfigFile);
      unlink($cConfigFile);
      // If we deleted a configuration in the dev environment we also need to reset the cache
      if ($cEnv == self::ENV_DEV) {
        $this->GetCachedConfigList(true);
      }
    }
  }

  /**
   * Delete the upload file structure
   * 
   * @param mixed $cEnv
   * @param mixed $cConfigName
   * @return void
   */
  private function DeleteFileSystem($cEnv, $cConfigName) {
    $cFilesPath = $this->GetFilesDirConfig($cEnv, $cConfigName);
    $iCount = $this->RecurseDelete($cFilesPath);
    $this->log('Removed filesystem: ' . $cFilesPath);
    if ($iCount) {
      $this->Idfix->FlashMessage("{$iCount} files removed from the filesystem.");
    }
  }

  /**
   * Delete the backup file structure
   * 
   * @param mixed $cEnv
   * @param mixed $cConfigName
   * @return void
   */
  private function DeleteBackupSystem($cEnv, $cConfigName) {
    $cFilesPath = $this->GetBackupDirConfig($cEnv, $cConfigName);
    $iCount = $this->RecurseDelete($cFilesPath);
    $this->log('Removed backups: ' . $cFilesPath);
    if ($iCount) {
      $this->Idfix->FlashMessage("{$iCount} backupfiles removed.");
    }
  }

  /**
   * Recursively delete a file structure
   * 
   * @param mixed $cDir
   * @return void
   */
  private function RecurseDelete($cDir) {
    static $iCount = 0;

    $aFileList = $this->_glob($cDir);
    foreach ($aFileList as $cFile) {
      if (is_dir($cFile)) {
        $this->RecurseDelete($cFile);
      }
      else {
        unlink($cFile);
        $iCount++;
      }
    }

    if (is_dir($cDir)) {
      rmdir($cDir);
    }

    return $iCount;
  }

  private function DeleteTableSpace($cEnv, $cConfigName) {
    $cTablename = $this->GetTableSpaceName($cEnv, $cConfigName);
    $this->log('Table dropped: ' . $cTablename);
    $this->Database->Query('DROP TABLE ' . $cTablename);
  }

  /**
   * IdfixOtap::Events3IdfixDeploy()
   * 
   * @param mixed $output We will not use this, use a header redirect to the contyrol panel
   * @return void
   */
  public function Events3IdfixActionDeploy(&$output) {
    if (!$this->Idfix->Access(self::PERM_ACCESS_CONTROLPANEL)) {
      return;
    }

    // Everything we need :-)
    $cConfigName = $this->Idfix->cConfigName;
    $cSourceEnv = $this->Idfix->cTableName;
    $cTargetEnv = $this->Idfix->cFieldName;

    // Are we doing up or downstream deployments?????
    // Upstream deployments should only deploy the configuration
    // downstream it is: data only!!
    $bUpstream = (boolean)($this->aEnvList[$cSourceEnv] < $this->aEnvList[$cTargetEnv]);

    if ($bUpstream) {
      $this->CopyConfigFile($cSourceEnv, $cTargetEnv, $cConfigName);
      $this->ResetConfigCache($cTargetEnv);
    }
    else {
      $this->CopyFileSystem($cSourceEnv, $cTargetEnv, $cConfigName);
      $this->CopyTableSpace($cSourceEnv, $cTargetEnv, $cConfigName);
    }


    $this->RedirectToControlPanel();
  }

  private function CopyConfigFile($cSourceEnv, $cTargetEnv, $cConfigName) {
    $cSourceConfigFile = $this->GetConfigFileName($cSourceEnv, $cConfigName);
    $cTargetConfigFile = $this->GetConfigFileName($cTargetEnv, $cConfigName);
    if (file_exists($cTargetConfigFile)) {
      unlink($cTargetConfigFile);
    }
    copy($cSourceConfigFile, $cTargetConfigFile);
  }

  /**
   * Clear the configuration cache for a specific environment
   * 
   * @param integer $cEnv
   * @return void
   */
  private function ResetConfigCache($cEnv, $cConfigName = '') {
    // Before we call the eventhandler we need to make sure
    // the current environment is changed
    $cOldEnvironment = $this->cCurrentEnvironment;
    $this->cCurrentEnvironment = $cEnv;


    // Call the correct method, and because we have
    // reset the environment.... everything OK
    $this->Idfix->ClearConfigCache($cConfigName);

    // And reset the environment
    $this->cCurrentEnvironment = $cOldEnvironment;
  }

  private function CopyFileSystem($cSourceEnv, $cTargetEnv, $cConfigName) {
    // First delete the old files
    $this->DeleteFileSystem($cTargetEnv, $cConfigName);
    // Get references to the two filestructures...
    $cSourceFilesPath = $this->GetFilesDirConfig($cSourceEnv, $cConfigName);
    $cTargetFilesPath = $this->GetFilesDirConfig($cTargetEnv, $cConfigName);
    // And do the recursdive magic
    $this->rcopy($cSourceFilesPath, $cTargetFilesPath);
  }

  /**
   * Recursive function
   * Found on internet.... looks nice...
   * Let's try it ...
   * 
   * @param mixed $src
   * @param mixed $dest
   * @return
   */
  private function rcopy($src, $dest) {
    // If source is not a directory stop processing
    if (!is_dir($src)) return false;

    // If the destination directory does not exist create it
    if (!is_dir($dest)) {
      if (!mkdir($dest)) {
        // If the destination directory could not be created stop processing
        return false;
      }
    }

    // Open the source directory to read in files
    $i = new DirectoryIterator($src);
    foreach ($i as $f) {
      if ($f->isFile()) {
        copy($f->getRealPath(), "$dest/" . $f->getFilename());
      }
      else
        if (!$f->isDot() && $f->isDir()) {
          $this->rcopy($f->getRealPath(), "$dest/$f");
        }
    }
  }

  private function CopyTableSpace($cSourceEnv, $cTargetEnv, $cConfigName) {
    // First delete the old table
    $this->DeleteTableSpace($cTargetEnv, $cConfigName);
    // Get the names for source and target tables
    $cSource = $this->GetTableSpaceName($cSourceEnv, $cConfigName);
    $cTarget = $this->GetTableSpaceName($cTargetEnv, $cConfigName);
    // .. and do the magic
    $this->Database->Query("CREATE TABLE {$cTarget} LIKE {$cSource}");
    $this->Database->Query("INSERT INTO {$cTarget} SELECT * FROM {$cSource}");
  }

  private function RedirectToControlPanel() {
    $cUrl = $this->Idfix->GetUrl($this->Idfix->cConfigName, '', '', 0, 0, 'Controlpanel');
    $this->Idfix->RedirectInline($cUrl);
  }

  /**
   * Show a grid with information for every environment from this configuration
   * Also show actions to perform.
   * 
   * @param mixed $output
   * @return void
   */
  public function Events3IdfixActionControlpanel(&$output) {
    //$this->IdfixDebug->Profiler(__method__, 'start');

    if (!$this->Idfix->Access(self::PERM_ACCESS_CONTROLPANEL)) {
      $this->Idfix->FlashMessage('No access allowed to the controlpanel.');
      return;
    }

    $aTemplateVars = array(
      'title' => $this->Idfix->aConfig['title'],
      'icon' => $this->Idfix->GetIconHTML($this->Idfix->aConfig),
      self::ENV_DEV => $this->RenderInfoPanel(self::ENV_DEV, 1),
      self::ENV_TEST => $this->RenderInfoPanel(self::ENV_TEST, 2),
      self::ENV_ACC => $this->RenderInfoPanel(self::ENV_ACC, 3),
      self::ENV_PROD => $this->RenderInfoPanel(self::ENV_PROD, 4),
      );

    $output = $this->RenderTemplate('ControlPanel', $aTemplateVars);

    //$this->IdfixDebug->Profiler(__method__, 'stop');
  }

  private function RenderInfoPanel($cEnv, $iVolgorde) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    static $aEnvironmentNames = array(
      self::ENV_DEV => array('name' => 'Development', 'class' => 'panel-info'),
      self::ENV_TEST => array('name' => 'Test', 'class' => 'panel-info'),
      self::ENV_ACC => array('name' => 'Acceptation', 'class' => 'panel-info'),
      self::ENV_PROD => array('name' => 'Production', 'class' => 'panel-info'),
      );

    $cConfigFile = $this->GetConfigFileName($cEnv, $this->Idfix->cConfigName);
    $bConfigPresent = file_exists($cConfigFile);

    // Create 0-indexed array of the environments
    $aEnvList = array_keys($this->aEnvList);
    $cDeploy = '';
    if ($iVolgorde < 4 and $bConfigPresent) {
      $cIcon = $this->Idfix->GetIconHTML('arrow-right');
      $cNextEnv = $aEnvList[$iVolgorde];
      $cNextEnvName = $aEnvironmentNames[$cNextEnv]['name'];
      $cDeploy .= $this->GetDeployButton($cEnv, $cNextEnv, "Deploy Configuration to <em>{$cNextEnvName}</em> environment {$cIcon}");
    }
    if ($iVolgorde > 1 and $bConfigPresent) {
      $cIcon = $this->Idfix->GetIconHTML('arrow-left');
      $cPrevEnv = $aEnvList[$iVolgorde - 2];
      $cPrevEnvName = $aEnvironmentNames[$cPrevEnv]['name'];
      $cDeploy .= $this->GetDeployButton($cEnv, $cPrevEnv, "{$cIcon} Copy data & files to <em>{$cPrevEnvName}</em> environment");
    }

    // Create the delete button but only if we have access and there is a configuration
    if ($bConfigPresent and $this->Idfix->Access(self::PERM_DELETE_CONFIG)) {
      $cDeploy .= $this->GetDeleteButton($cEnv);
    }

    // Create Add/Edit button for DEV
    $cEditButton = '';
    if ($cEnv == self::ENV_DEV) {
      $cText = (!$bConfigPresent ? 'Add' : 'Edit') . ' Configuration';
      $cEditButton = $this->GetEditButton($cText);
    }

    // Create url to the right environment
    $cIcon = $this->Idfix->GetIconHTML('log-in');
    $cUrl = $this->Idfix->GetUrl('', '', '', 1, 0, 'list', array(self::GET_OTAP => $cEnv));
    $cEnvName = $aEnvironmentNames[$cEnv]['name'];
    $cHref = "<a href=\"{$cUrl}\">{$cIcon} {$cEnvName}</a>";
    if (!$bConfigPresent) {
      $cHref = $cEnvName;
    }
    $aTemplateVars = array(
      'title' => $cHref,
      'class' => $aEnvironmentNames[$cEnv]['class'],
      'deploy' => $cDeploy,
      'fileinfo' => $this->RenderInfoFileSystem($cEnv),
      //'password' => $this->RenderTemplate('Password'),
      'password' => '',
      'edit' => $cEditButton,
      //'backup' => '',
      // Backups switched off due to ZipArchive issues on GAE
      'backup' => $this->GetBackupFullTemplate($cEnv, $this->Idfix->cConfigName),
      );

    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $this->RenderTemplate('ControlPanelItem', $aTemplateVars);
  }

  /**
   * Render a deploy Button
   * 
   * @param mixed $cEnvFrom
   * @param mixed $cEnvTo
   * @return void
   */
  private function GetDeployButton($cEnvFrom, $cEnvTo, $cName) {
    $cUrl = $this->Idfix->GetUrl($this->Idfix->cConfigName, $cEnvFrom, $cEnvTo, 0, 0, 'deploy');
    $cButton = "<a  onclick=\"confirm('Are you sure you want to proceed? The target configuration and/or dataset will be destroyed before deployment!')\" href=\"{$cUrl}\" class=\"btn btn-primary btn-block\" role=\"button\">{$cName}</a>";
    return $cButton;
  }

  private function GetEditButton($cTitle) {
    $cIcon = $this->Idfix->GetIconHTML('edit');
    $cUrl = $this->Idfix->GetUrl($this->Idfix->cConfigName, '', '', 0, 0, 'editconfig');
    $cButton = "<a  href=\"{$cUrl}\" class=\"btn btn-default btn-block\" role=\"button\">{$cIcon} {$cTitle}</a>";
    return $cButton;
  }

  private function GetDeleteButton($cEnv) {
    $cIcon = $this->Idfix->GetIconHTML('remove');
    $cUrl = $this->Idfix->GetUrl($this->Idfix->cConfigName, $cEnv, '', 0, 0, 'removeconfig');
    $cButton = "<a onclick=\"confirm('Are you sure you want to delete thuis configuration and all of it\'s data?')\" href=\"{$cUrl}\" class=\"btn btn-warning btn-block\" role=\"button\">{$cIcon} Delete Environment (config & data & files)</a>";
    return $cButton;
  }

  /**
   * Create a key/Value table with filesystem information
   * 
   * @param mixed $cEnv
   * @return void
   */
  private function RenderInfoFileSystem($cEnv, $bResetCache = false) {
    $this->IdfixDebug->Profiler(__method__, 'start');

    $cConfigName = $this->Idfix->cConfigName;

    $cCacheKey = __method__ . $cConfigName . $cEnv;
    if ($bResetCache) {
      $this->ev3->CacheDelete($cCacheKey);
    }

    $aTable = '';
    // Only use caching on Google App Engine because of poor file performance
    if ($this->ev3->GAE_IsPlatform()) {
      $aTable = $this->ev3->CacheGet($cCacheKey);
    }


    // Build the table if it isnt there
    if (!is_array($aTable)) {

      $aTable = array();
      $cPublicPath = $this->ev3->PublicPath;
      $aTable[] = array(
        'title' => 'Public File System',
        'class' => (is_dir($cPublicPath) ? 'success' : 'danger'),
        'info' => $cPublicPath,
        'description' => '',
        );

      $cFilesPath = $this->GetFilesDirConfig($cEnv, $cConfigName);
      $aTable[] = array(
        'title' => 'Upload directory',
        'class' => (is_dir($cFilesPath) ? 'success' : 'danger'),
        'info' => str_ireplace($cPublicPath, '', $cFilesPath),
        'description' => '',
        );

      $cConfigFile = $this->GetConfigFileName($cEnv, $cConfigName);
      $cHashCode = file_exists($cConfigFile) ? md5_file($cConfigFile) : 'File not found';
      $aTable[] = array(
        'title' => 'Configuration File',
        'class' => (file_exists($cConfigFile) ? 'success' : 'danger'),
        'info' => str_ireplace($cPublicPath, '', $cConfigFile) . '<br />' . $cHashCode,
        'description' => '',
        );

      $cTableSpace = $this->GetTableSpaceName($cEnv, $cConfigName);
      $iRecords = $this->Database->CountRecords($cTableSpace);
      $aTable[] = array(
        'title' => 'Data Table',
        'class' => count($this->Database->ShowTables($cTableSpace)) > 0 ? 'success' : 'danger',
        'info' => $cTableSpace . " (#{$iRecords} objects)",
        'description' => '',
        );

      //Store this info ..
      $this->ev3->CacheSet($cCacheKey, $aTable);

    }


    $aTemplateVars = compact('aTable');
    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $this->RenderTemplate('ControlPanelTable', $aTemplateVars);
  }

  /**
   * Render an Idfix template
   * 
   * @param string $cTemplateName Name of the template without path and extention
   * @param array $aVars Template variables (@see Template Module)
   * @return string Rendered template
   */
  private function RenderTemplate($cTemplateName, $aVars = array()) {
    $cTemplateFile = dirname(__file__) . "/templates/{$cTemplateName}.php";
    $return = $this->Template->Render($cTemplateFile, $aVars);
    return $return;
  }

  /**
   * Create the full directory structure for
   * the otap system.
   * It is called and check form the unittest
   * So no need for runtime performance issues.
   * 
   * @return void
   */
  public function SetupDirectoryStructure() {
    $aEnvironments = array(
      self::ENV_ACC,
      self::ENV_DEV,
      self::ENV_PROD,
      self::ENV_TEST);

    $cDir2Check = $this->GetBaseDir();
    $this->CheckOrCreateDirectory($cDir2Check);

    foreach ($aEnvironments as $cEnvironment) {
      $cDirEnv = $cDir2Check . '/' . $cEnvironment;
      $this->CheckOrCreateDirectory($cDirEnv);
      $this->CheckOrCreateDirectory($cDirEnv . '/config');
      $this->CheckOrCreateDirectory($cDirEnv . '/files');
      $this->CheckOrCreateDirectory($cDirEnv . '/backup');
    }
  }

  private function CheckOrCreateDirectory($cDir) {
    if (!is_dir($cDir)) {
      mkdir($cDir);
    }
  }

  private function GetBaseDir() {
    return $this->ev3->PublicPath . '/otap';
  }

  private function GetConfigDir($cEnv = null) {
    $cEnv = ($cEnv) ? $cEnv : $this->cCurrentEnvironment;
    return $this->GetBaseDir() . '/' . $cEnv . '/config';
  }

  private function GetFilesDir($cEnv = null) {
    $cEnv = ($cEnv) ? $cEnv : $this->cCurrentEnvironment;
    return $this->GetBaseDir() . '/' . $cEnv . '/files';
  }

  private function GetTableSpaceName($cEnv, $cConfigName) {
    $cConfigName = $this->Idfix->ValidIdentifier($cConfigName);
    return 'idfix_otap_' . $cEnv . '_' . $cConfigName;
  }

  private function GetConfigFileName($cEnv, $cConfigName) {
    $cConfigName = $this->Idfix->ValidIdentifier($cConfigName);
    return $this->GetConfigDir($cEnv) . '/' . $cConfigName . '.idfix';
  }

  private function GetFilesDirConfig($cEnv, $cConfigName) {
    $cConfigName = $this->Idfix->ValidIdentifier($cConfigName);
    return $this->GetFilesDir($cEnv) . '/' . $cConfigName;
  }

  /****************************
  B A C K U P    S E C T I O N
  ****************************/

  private function GetBackupDir($cEnv = null) {
    $cEnv = ($cEnv) ? $cEnv : $this->cCurrentEnvironment;
    return $this->GetBaseDir() . '/' . $cEnv . '/backup';
  }

  private function GetBackupDirConfig($cEnv, $cConfigName) {
    $cConfigName = $this->Idfix->ValidIdentifier($cConfigName);
    return $this->GetBackupDir($cEnv) . '/' . $cConfigName;
  }

  private function GetBackupDirFiles($cEnv, $cConfigName, $bResetCache = false) {
    $cDirname = $this->GetBackupDirConfig($cEnv, $cConfigName);
    // GAE
    // $aFiles = (array )glob($cDirname . '/*.*');
    $aFiles = $this->_glob($cDirname, '*.*', $bResetCache);
    rsort($aFiles);
    return $aFiles;
  }

  private function GetBackupDirFilesDisplay($cEnv, $cConfigName) {
    $cBasePath = $this->ev3->BasePath;
    $aFiles = $this->GetBackupDirFiles($cEnv, $cConfigName);
    $aFilesAsDisplay = array();
    foreach ($aFiles as $cFileName) {

      $cRelativeFilename = str_ireplace($cBasePath, '', $cFileName);
      $cRelativeFilename = trim($cRelativeFilename, '/');
      $cDownUrl = $this->ev3->BasePathUrl . '/' . $cRelativeFilename;
      if ($this->ev3->GAE_IsPlatform()) {
        $cDownUrl = CloudStorageTools::getPublicUrl($cFileName, false);
      }


      $aTemplateVars = array(
        'name' => date('l d F Y (H:i)', (integer)basename($cFileName)),
        'file' => $cFileName,
        'delete' => $this->GetBackupUrl($cEnv, $cFileName, self::BACKUP_ACTION_DELETE),
        'restore' => $this->GetBackupUrl($cEnv, $cFileName, self::BACKUP_ACTION_RESTORE),
        //'download' => $this->GetBackupUrl($cEnv, $cFileName, self::BACKUP_ACTION_DOWNLOAD),
        'download' => $cDownUrl,
        'size' => (integer)(@filesize($cFileName) / 1024) . ' kb.',
        'delete_icon' => $this->Idfix->GetIconHTML('remove'),
        'restore_icon' => $this->Idfix->GetIconHTML('open'),
        'download_icon' => $this->Idfix->GetIconHTML('save'),
        );
      $aFilesAsDisplay[] = $this->RenderTemplate('ControlPanelItemBackupListItem', $aTemplateVars);
    }
    $aTemplateVars['list'] = $aFilesAsDisplay;
    return $this->RenderTemplate('ControlPanelItemBackupList', $aTemplateVars);
  }

  private function GetBackupActions($cEnv, $cConfigName) {
    $aTemplateVars = array(
      'new' => $this->GetBackupUrl($cEnv, '', self::BACKUP_ACTION_NEW),
      'icon_new' => $this->Idfix->GetIconHTML('floppy-save'),
      'upload' => $this->GetBackupUrl($cEnv, '', self::BACKUP_ACTION_UPLOAD),
      'icon_upload' => $this->Idfix->GetIconHTML('open'),
      );
    return $this->RenderTemplate('ControlPanelItemBackupAction', $aTemplateVars);
  }

  private function GetBackupFullTemplate($cEnv, $cConfigName) {
    $this->IdfixDebug->Profiler(__method__, 'start');

    // No ZIPPING???
    // No need to display this backup subsection
    if (!$this->Zip->Available()) {
      return '';
    }

    $aTemplateVars = array(
      'action' => $this->GetBackupActions($cEnv, $cConfigName),
      'list' => $this->GetBackupDirFilesDisplay($cEnv, $cConfigName),
      );
    $cHtml = $this->RenderTemplate('ControlPanelItemBackup', $aTemplateVars);
    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $cHtml;
  }

  private function GetBackupUrl($cEnv, $cFileName, $iAction) {
    return $this->Idfix->GetUrl('', $cEnv, basename($cFileName), $iAction, null, 'backup');
  }

  public function Events3IdfixActionBackup(&$output) {
    $cEnv = $this->Idfix->cTableName;
    $iAction = $this->Idfix->iObject;
    $cFileName = $this->Idfix->cFieldName;

    // Some actions need to be scheduled
    if ($iAction == self::BACKUP_ACTION_NEW or $iAction == self::BACKUP_ACTION_RESTORE) {
      // Create background task
      $cUrl = $this->Idfix->GetUrl($this->Idfix->cConfigName, $cEnv, $cFileName, $iAction, 0, 'Backuptask');
      $this->Idfix->GetSetClientTaskUrl($cUrl);
      $this->log('Backgroundtask created');
    }
    else {
      // Call the taskhandler direct
      $this->Events3IdfixActionBackuptask();
    }
    $this->RedirectToControlPanel();


  }

  /**
   * Anonymous task handler
   * 
   * @return void
   */
  public function Events3IdfixActionBackuptask() {
    $cEnv = $this->Idfix->cTableName;
    $iAction = $this->Idfix->iObject;
    $cFileName = $this->Idfix->cFieldName;

    $cFullFileName = $this->GetBackupDirConfig($cEnv, $this->Idfix->cConfigName) . '/' . $cFileName;

    if (self::BACKUP_ACTION_DELETE == $iAction) {
      $this->BackupActionDelete($cFullFileName);
      $this->GetBackupDirFiles($cEnv, $this->Idfix->cConfigName, true);
    }
    elseif (self::BACKUP_ACTION_RESTORE == $iAction) {
      $this->BackupActionRestore($cFullFileName, $cEnv, $this->Idfix->cConfigName);
    }
    elseif (self::BACKUP_ACTION_NEW == $iAction) {
      $this->BackupActionNew($cEnv);
      $this->GetBackupDirFiles($cEnv, $this->Idfix->cConfigName, true);
    }
    elseif (self::BACKUP_ACTION_UPLOAD == $iAction) {
      $this->BackupActionUpload($cEnv);
      $this->GetBackupDirFiles($cEnv, $this->Idfix->cConfigName, true);
    }
    elseif (self::BACKUP_ACTION_DOWNLOAD == $iAction) {
      $this->BackupActionDownload($cFullFileName);
    }

    $cConfig = $this->Idfix->cConfigName;
    $this->log("Background backup handler: Environmenr:{$cEnv} Action:{$iAction} Config:{$cConfig}");
    //exit();
  }

  private function BackupActionUpload($cEnv) {
    $cDir = $this->GetBackupDirConfig($cEnv, $this->Idfix->cConfigName);
    if (isset($_FILES['upload']) and !$_FILES['upload']['error']) {
      $cTmpName = $_FILES['upload']['tmp_name'];
      $cBackupName = $cDir . '/' . $_FILES['upload']['name'];
      if (file_exists($cTmpName) and !file_exists($cBackupName)) {
        copy($cTmpName, $cBackupName);
      }
    }
  }

  private function BackupActionDownload($cFullFileName) {
    //CloudStorageTools::serve($cFullFileName);
  }


  private function BackupActionDelete($cFullFileName) {
    if (file_exists($cFullFileName)) {
      $this->Idfix->FlashMessage($cFullFileName . '<br />This file has been deleted.');
      unlink($cFullFileName);
    }
  }

  private function BackupActionNew($cEnv) {
    // Create backup directory if it does not exist
    $cDir = $this->GetBackupDirConfig($cEnv, $this->Idfix->cConfigName);
    $this->CheckOrCreateDirectory($cDir);
    // Check the directory we need to compress
    $cBaseDir = $this->GetFilesDirConfig($cEnv, $this->Idfix->cConfigName);
    $this->CheckOrCreateDirectory($cBaseDir);
    // And create the archive name
    $iTimeStamp = time();
    $cCheckSum = substr(md5($iTimeStamp . __function__ ), 0, 10);
    $cFullFileName = $cDir . '/' . $iTimeStamp . '_' . $cCheckSum . '.zip';

    // Create tabledump
    $cDumpFile = $cBaseDir . '/dbdump.txt';
    $cTablename = $this->GetTableSpaceName($cEnv, $this->Idfix->cConfigName);
    $this->Database->DumpTableData($cDumpFile, $cTablename);

    // Copy the configurationfile
    $cSource = $this->GetConfigFileName($cEnv, $this->Idfix->cConfigName);
    $cTarget = $cBaseDir . '/config.txt';
    @copy($cSource, $cTarget);

    $this->Zip->ZipDirectory($cBaseDir, $cFullFileName);

    // Destroy the temporary files
    unlink($cTarget);
    unlink($cDumpFile);
  }

  private function BackupActionRestore($cFullFileName, $cEnv, $cConfigName) {

    // Delete the file system
    $this->DeleteFileSystem($cEnv, $cConfigName);
    $cFilesDir = $this->GetFilesDirConfig($cEnv, $cConfigName);
    $this->CheckOrCreateDirectory($cFilesDir);

    // .. and restore form tar.gz, overwriting existing files ... if any ...
    $this->Zip->UnzipDirectory($cFilesDir, $cFullFileName);

    // Now copy the configuration file
    $cBaseConfigFile = $cFilesDir . '/config.txt';
    if (file_exists($cBaseConfigFile)) {
      // Remove original config file
      $this->DeleteConfigFile($cEnv, $cConfigName);
      $cConfigFileName = $this->GetConfigFileName($cEnv, $cConfigName);
      copy($cBaseConfigFile, $cConfigFileName);
      unlink($cBaseConfigFile);
    }

    // ... and restore the data
    $cDumpFile = $cFilesDir . '/dbdump.txt';
    if (file_exists($cDumpFile)) {
      $cTablename = $this->GetTableSpaceName($cEnv, $cConfigName);
      $this->IdfixStorage->check($cTablename);
      $this->Database->RestoreTableData($cDumpFile, $cTablename);
      unlink($cDumpFile);
    }
  }

  /**
   * IdfixOtap::_glob()
   * 
   * Replacement function for glob() beacuse it is not present on GAE
   * 
   * Every interaction with the GCS system should be fully cached
   * because there is a serious performance hit in accessing this
   * filesystem
   * 
   * @param string $cDirectory
   * @param string $cWildCard
   * @param boolean $bResetCache Clear the memory cache 
   * @return array filelist
   */
  private function _glob($sDir, $cWildCard = '*', $bResetCache = false) {
    $this->IdfixDebug->Profiler(__method__, 'start');

    // Try the cache first
    $cCacheKey = __method__ . '/' . $sDir . '/' . $cWildCard;

    // Do we need to reset the cache???
    if ($bResetCache) {
      $this->ev3->CacheDelete($cCacheKey);
    }

    // Is the cache present?? Fast path out of here ....
    $aList = $this->ev3->CacheGet($cCacheKey);
    if (is_array($aList)) {
      $this->IdfixDebug->Profiler(__method__, 'stop');
      return $aList;
    }

    $aList = array();
    if (is_dir($sDir) and $rhandle = opendir($sDir)) {
      while ($cFileName = readdir($rhandle)) {
        // Never add . and ..
        if ($cFileName == '.' || $cFileName == '..') {
          continue;
        }

        // Check wildcard
        if (fnmatch($cWildCard, $cFileName)) {
          $aList[] = $sDir . '/' . $cFileName;
        }
      }
    }

    // Store the call in the cache
    $this->ev3->CacheSet($cCacheKey, $aList);

    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $aList;
  }

  /**
   * Get the configurations
   * Simple wrapper for the _glob method
   *  
   * @param bool $bResetCache
   * @return void
   */
  private function GetCachedConfigList($bResetCache = false) {
    return $this->_glob($this->GetConfigDir(self::ENV_DEV), '*.idfix', $bResetCache);
  }


}
