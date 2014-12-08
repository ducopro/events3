<?php


/**
 * This is all the code we nee to create a full-fledged backoffice system
 * for administration purposes.
 * 
 * Note that the base systyem is build using an Idfix script with
 * custom event handlers for extra functionality
 * 
 * It listens to the configuration name 'idbo'
 * Which is short for Idfix backoffice
 * 
 */

class IdfixBackoffice extends Events3Module {

  // Name of the config we will listen for
  const NAME = 'idbo';

  /**
   * Clear the configuration cache in development
   * every time we access it.
   * 
   * @return void
   */
  public function Events3IdfixConfigCache() {

    if (!$this->ev3->GAE_IsRuntime() and $this->Idfix->cConfigName == self::NAME) {
      $this->Idfix->IdfixConfigCache = false;
      //$this->Idfix->FlashMessage('Cache disabled for '.__METHOD__);
    }

  }
  /**
   * We create this handler as a AFTER eventhandler
   * Because we need to override every call to this configuration
   * and substitue it with our own config
   * 
   * @return void
   */
  public function Events3IdfixGetConfig() {
    // Which config did we call?
    $cConfigName = $this->Idfix->cConfigName;

    // is it a config we need to listen to???
    if ($cConfigName == self::NAME) {
      $cFileName = dirname(__file__) . '/backoffice.idfix';
      // Parse our config
      $aConfig = $this->IdfixParse->Parse($cFileName);
      // And call the same event as called from the IdfixParse module
      // Otap module configures tablespace and filespace
      // user module adds the user system
      $this->Idfix->Event('AfterParse', $aConfig);
      $this->Idfix->aConfig = $aConfig;
    }
  }

  public function Events3IdfixActionDeleteBefore(&$output) {
    // Only act on IDBO config
    $bIsCorrectConfig = (boolean)($this->Idfix->cConfigName == self::NAME);
    if (!$bIsCorrectConfig) {
      return;
    }

    // Only act on deletion of configurations!!
    $aRecord = $this->IdfixStorage->LoadRecord($this->Idfix->iObject);
    $bIsCorrectType = (boolean)(isset($aRecord['TypeID']) and $aRecord['TypeID'] == 20);
    if (!$bIsCorrectType) {
      return;
    }

    $cConfigName = $aRecord['Id'];
    $this->IdfixOtap->DeleteFullConfig($cConfigName);
    $this->log('Deleted configuration: ' . $cConfigName, LOG_ALERT);
    //$this->Idfix->FlashMessage('Called just before deleting config ' . $cConfigName);
  }

  /**
   * Handler called just before saving a record
   * 
   * NEW
   *    - Create config.idfix file
   *    - Get the config
   *    - Create Password   
   *    - Create new OTAP administrator
   *    - Send mail + CC to Customer mailadress
   *
   * 
   * @return void
   */
  public function Events3IdfixSaveRecord(&$aData) {
    // Only act on NEW records with TypeId=20 from the correct confifg!!!
    $bIsCorrectConfig = (boolean)($this->Idfix->cConfigName == self::NAME);
    $bIsNew = (boolean)(!isset($aData['MainID']));
    $bIsCorrectType = (boolean)(isset($aData['TypeID']) AND ($aData['TypeID'] == 20));
    //$this->IdfixDebug->Debug(__method__, get_defined_vars());
    //$this->Idfix->FlashMessage('In Handler');
    if (!$bIsCorrectConfig or !$bIsCorrectType or !$bIsNew) {
      return;
    }

    $this->log('Start proces ' . __method__);

    // Get all the data from the record to be saved
    $aData['Id'] = $this->Idfix->ValidIdentifier($aData['Id']);
    $cNewConfigName = $aData['Id'];
    $bSendEmail = (boolean)($aData['SubTypeID'] == 1);
    $cEmailOtapAdmin = $aData['Name'];
    $cPasswordOtapAdmin = substr(md5($cEmailOtapAdmin), 0, 8);

    // Create The idfix file, if it does not already exist!!!
    // So if we accidently configure an existing config..... no problem
    $this->IdfixOtap->CreateEmptyConfigInDev($cNewConfigName);
    $this->log('Created config: ' . $cNewConfigName);

    // Now trigger all the events for builing the config and creating
    // the tables and superuser
    // In the background we need to save the config name and the current config
    $aOldConfig = $this->Idfix->aConfig;
    $cOldConfigName = $this->Idfix->cConfigName;
    $cOldEnvironment = $this->IdfixOtap->cCurrentEnvironment;
    // Set the new values
    $this->Idfix->cConfigName = $cNewConfigName;
    $this->Idfix->aConfig = array();
    $this->IdfixOtap->cCurrentEnvironment = IdfixOtap::ENV_DEV;
    // Call the event handler
    $this->Idfix->Event('GetConfig');
    // Create the tables, and superuser
    $this->IdfixStorage->check();
    // Remove all userrecords ....
    $this->IdfixStorage->DeleteRecords('TypeID', '9999');
    // ... and only create OTAP administrator
    $aUser = array(
      'TypeID' => 9999,
      'Name' => $cEmailOtapAdmin,
      'UserName' => 'DTAP Admin',
      'Char_1' => $this->IdfixUser->CreateHashValue($cPasswordOtapAdmin),
      'SubTypeID' => 2,
      'ParentID' => 0,
      );
    $this->IdfixStorage->SaveRecord($aUser);
    // Reset the configuration
    $this->Idfix->cConfigName = $cOldConfigName;
    $this->Idfix->aConfig = $aOldConfig;
    $this->IdfixOtap->cCurrentEnvironment = $cOldEnvironment;

    // Only one thing left to do.... Send the mail
    if ($bSendEmail) {
      $aTemplateVars = array(
        'url' => $this->Idfix->GetUrl($cNewConfigName, '', '', 0, 0, 'loginform', array(IdfixOtap::GET_OTAP => 'dev')),
        'password' => $cPasswordOtapAdmin,
        'email' => $cEmailOtapAdmin,
        'config' => $cNewConfigName,
        );
      $cEmailBody = $this->Idfix->RenderTemplate('MailNewConfig', $aTemplateVars);
      $cSubject = 'Your new environment on the Idfix SAAS platform';
      $aUser = array('Name' => $cEmailOtapAdmin);
      $this->IdfixMail->Mail($cEmailBody, $cSubject, $aUser);

      $this->log('Mail send to: ' . $cEmailOtapAdmin);
      $this->log($cEmailBody);
    }

    $this->IdfixDebug->Debug(__method__, get_defined_vars());

    $this->log('Stop proces ' . __method__);
  }


  /**
   * Tijdsgestuurde logdigestor
   * 
   * @return void
   */
  public function Events3CronDay() {
    // First load up the idbo config ...
    $this->Idfix->cConfigName = self::NAME;
    $this->Idfix->Event('GetConfig');

    // Get timestamps for start of this day
    $cDateTodayStart = date('Ymd 00:00:00');
    $iStartOfThisDay = strtotime($cDateTodayStart);
    $iStartOfPreviousDay = ($iStartOfThisDay - (24 * 60 * 60));


    // For debugging purposes!!!! REMOVE LATER!!!
    //$iStartOfThisDay = $iStartOfThisDay + ((24 * 60 * 60));


    $cFrom = date('Y-m-d H:i:s', $iStartOfPreviousDay);
    $cTo = date('Y-m-d H:i:s', $iStartOfThisDay);
    //print_r(get_defined_vars());
    $this->log('Starting LOG aggregation: ' . $cFrom . ' to ' . $cTo);

    $options = array(
      // Fetch last 24 hours of log data
      'start_time' => ($iStartOfPreviousDay * 1e6),
      // End time is Now
      'end_time' => ($iStartOfThisDay * 1e6),
      // Include all Application Logs (i.e. your debugging output)
      'include_app_logs' => false,
      // Filter out log records based on severity
      //'minimum_log_level' => LogService::LEVEL_DEBUG,
      );

    $logs = google\appengine\api\log\LogService::fetch($options);

    $aInfo = array();
    $count = 0;
    foreach ($logs as $log) {
      // Full resouce name
      $cResource = $log->getResource();
      $aResource = explode('/', $cResource);
      $cConfig = $aResource[1];
      if (!isset($aInfo[$cConfig])) $aInfo[$cConfig] = 0;
      $aInfo[$cConfig]++;
      $count++;
    }

    $this->log('Logrecords processed ' . $count);

    // Next fase, store them into the corresponding logrecords
    $cDate = date('Y-m-d', $iStartOfPreviousDay);
    foreach ($aInfo as $cConfigName => $iHitCount) {
      // Get Parentrecord
      $aRecords = $this->IdfixStorage->LoadAllRecords(20, null, null, array("Id = '{$cConfigName}'"), 1);
      $aRecord = array_shift($aRecords);
      if (isset($aRecord['MainID'])) {
        // This is where we need to store the counter
        $iParentID = $aRecord['MainID'];
        // Check if record exists
        $bCounterAlreadyThere = $this->HasHitRecord($iParentID, $cDate);
        $bCounterAlreadyThere = false;
        // Set up the record
        if ($bCounterAlreadyThere) {
          $this->log('Configcounter already stored: ' . $cConfigName);
        }
        else {
          // Also collect StoragePoints and DataPoints
          $aPoints = $this->CollectStorageAndDataPoints($cConfigName);
          $aNewRecord = array(
            'TypeID' => 30,
            'ParentID' => $iParentID,
            'Id' => $cDate,
            'SubTypeID' => $iHitCount,
            'RefID' => $aPoints['storage'],
            'Int_1' => $aPoints['data'],
            );
          $this->IdfixStorage->SaveRecord($aNewRecord);
          $this->log('Config found: ' . $cConfigName . ' Hits: ' . $iHitCount);
        }
      }
      else {
        //$this->log('Config not found: ' . $cConfigName);
      }

      //print_r(get_defined_vars());
    }
    //$this->IdfixDebug->Debug(__method__, $aRecords);

  }

  /**
   * Check if we already have a hit record
   * 
   * @param integer $iParentID
   * @param string $cDate
   * @return boolean true if record exists
   */
  private function HasHitRecord($iParentID, $cDate) {
    $aRecords = $this->IdfixStorage->LoadAllRecords(30, $iParentID, null, array("Id = '{$cDate}'"), 1);
    $aRecord = array_shift($aRecords);
    return (boolean)isset($aRecord['MainID']);
  }

  /**
   * Ok, Go through all the environments and collect statistics
   * about storage- and data usage
   * 
   * @param mixed $cConfigName
   * @return
   */
  private function CollectStorageAndDataPoints($cConfigName) {
    $aReturn = array('storage' => 0, 'data' => 0);

    // We have 4 environments to process
    $aInfo = array();
    foreach ($this->IdfixOtap->aEnvList as $cEnv => $cEnvNumber) {
      $aInfo[$cEnv] = $this->CollectStorageAndDataPointsByEnvironment($cConfigName, $cEnv);
    }

    // Now postprocess verything into a grand total of storage and data point
    // But also create a good log record
    $iStoragePointsTotal = 0;
    $iDataPointsTotal = 0;
    $this->log(">>>  Storage- and datapoints collection: {$cConfigName} >>>");
    foreach ($aInfo as $cEnv => $aTables) {
      foreach ($aTables as $iTableId => $aTableInfo) {
        $this->log("{$cEnv}:{$iTableId} #records:{$aTableInfo['record_count']} #fields:{$aTableInfo['field_count']} #storage:{$aTableInfo['avg_storage']}");
        $aReturn['data'] += $aTableInfo['data_points'];
        $aReturn['storage'] += $aTableInfo['storage_points'];
      }
    }

    // Tiny thing.....
    // Storage points are measured in 100 MB packages. Right now it is only megabytes
    // So we need a little conversion.
    $aReturn['storage'] = ceil($aReturn['storage'] / 100);
    $this->log("Total StoragePoints:{$aReturn['storage']} DataPoints:{$aReturn['data']}");
    //print_r($aInfo);

    return $aReturn;
  }

  private function CollectStorageAndDataPointsByEnvironment($cConfigName, $cEnv) {
    // Store all old values
    $aOldConfig = $this->Idfix->aConfig;
    $cOldConfigName = $this->Idfix->cConfigName;
    $cOldEnvironment = $this->IdfixOtap->cCurrentEnvironment;
    // Set the new values
    $this->Idfix->cConfigName = $cConfigName;
    $this->Idfix->aConfig = array();
    $this->IdfixOtap->cCurrentEnvironment = $cEnv;
    // Call the event handler
    $this->Idfix->Event('GetConfig');

    // Do NOT turn on in production!!
    //$this->log($this->Idfix->aConfig);

    // Get all the information from the current environment
    $aReturn = $this->CollectAllFromCurrentEnvironment();
    //$this->log($aReturn);

    // Reset the configuration
    $this->Idfix->cConfigName = $cOldConfigName;
    $this->Idfix->aConfig = $aOldConfig;
    $this->IdfixOtap->cCurrentEnvironment = $cOldEnvironment;

    return $aReturn;
  }

  private function CollectAllFromCurrentEnvironment() {
    // Get all the recordcounts from the tablespace
    $cTableName = $this->Idfix->aConfig['tablespace'];
    $cSql = "SELECT TypeID, count(*) as count FROM {$cTableName} GROUP BY TypeID";
    $aData = $this->Database->DataQuery($cSql);

    //$this->log($cSql);

    // Process them all into a return array
    $aReturn = array();
    foreach ($aData as $aRow) {
      $iTypeID = (integer)$aRow['TypeID'];
      $iCount = (integer)$aRow['count'];
      $iAverageStorageInMb = $this->GetAverageStorageInMB($iTypeID);
      $iFieldCount = $this->GetFieldCount($iTypeID);
      $aReturn[$iTypeID] = array(
        'record_count' => $iCount,
        'field_count' => $iFieldCount,
        'avg_storage' => $iAverageStorageInMb,
        'data_points' => $iCount * $iFieldCount,
        'storage_points' => $iCount * $iAverageStorageInMb,
        );
    }
    return $aReturn;
  }

  private function GetAverageStorageInMB($iTypeID) {
    $iReturn = 0;
    foreach ($this->Idfix->aConfig['tables'] as $cTableName => $aTableConfig) {
      if ($aTableConfig['id'] == $iTypeID) {
        foreach ($this->Idfix->aConfig['tables'][$cTableName]['fields'] as $cFieldName => $aFieldConfig) {
          if ($aFieldConfig['type'] == 'file') {
            // Now find the maximum size, make it MB's and get half of it
            // finally round it up to the next MB
            $iBytes = (integer)@$aFieldConfig['max'];
            if ($iBytes) {
              $iMb = ceil(($iBytes / (1024 * 1024)) / 2);
              $iReturn += $iMb;
            }
            //$this->log("{$cFieldName} {$iBytes}  TOTAL: {$iReturn}");
          }
        }
      }
    }
    //$this->log( __METHOD__ . ' MB ' . $iReturn);
    return $iReturn;
  }

  private function GetFieldCount($iTypeID) {
    $iReturn = 0;
    foreach ($this->Idfix->aConfig['tables'] as $cTableName => $aTableConfig) {
      if ($aTableConfig['id'] == $iTypeID) {
        $iReturn = count($this->Idfix->aConfig['tables'][$cTableName]['fields']);
        //$this->log('Fieldcount found for table ' . $cTableName . ' fields #' . $iReturn);
        return $iReturn;
      }
    }
    //$this->log('No fieldcount found for ' . $iTypeID);
    return $iReturn;
  }

  /**
   * Subsection for monthly cron process for 
   * getting all the aggregated information and creating the bills ... 
   */

  public function Events3CronMonth() {
    // First load up the idbo config ...
    $this->Idfix->cConfigName = self::NAME;
    $this->Idfix->Event('GetConfig');

    $this->log('Starting monthly cron process');

    // Scan all the resellers and do the processing
    $aResellers = $this->IdfixStorage->LoadAllRecords(100);
    foreach ($aResellers as $iResellerID => $aReseller) {
      $this->EcmReseller($aReseller);
    }
    $this->log('Finished monthly cron process for #resellers: ' . count($aResellers));
  }

  private function EcmReseller($aReseller) {
    // Get the reseller ID
    $iResellerID = $aReseller['MainID'];
    // Load all the child companies
    $aCompanies = $this->IdfixStorage->LoadAllRecords(10, $iResellerID);
    // And process them 1 by 1
    foreach ($aCompanies as $iCompanyID => $aCompany) {
      $this->EcmCompany($aReseller, $aCompany);
    }
  }

  private function EcmCompany($aReseller, $aCompany) {
    // Get the Company ID
    $iCompanyID = $aCompany['MainID'];
    // Load all the child configurations
    $aConfigList = $this->IdfixStorage->LoadAllRecords(20, $iCompanyID);
    // And process them 1 by 1
    foreach ($aConfigList as $aConfig) {
      // Only process them if they are billable!!!!
      if ($aConfig['RefID']) {
        $this->EcmConfig($aReseller, $aCompany, $aConfig);
      }

    }
  }
  private function EcmInvoiceExists($cInvoiceID, $iConfigID) {
    // 60 = TypeID Invoice
    $aRecords = $this->IdfixStorage->LoadAllRecords(60, $iConfigID, '', array("Id = '{$cInvoiceID}'"), 1);
    return (boolean)(count($aRecords) > 0);
  }

  private function EcmConfig($aReseller, $aCompany, $aConfig) {
    // What's the config ID
    $iConfigID = $aConfig['MainID'];
    // Which month to process?
    $cLastMonth = date('Y-m', strtotime('last month'));
    // Create an invoice ID
    $cInvoiceID = $aReseller['MainID'] . '-' . $aCompany['MainID'] . '-' . $aConfig['Id'] . '-' . $cLastMonth;
    // Now we can do a quick check to see if this invoice already exists
    // If it does, we do not need to do it again!!!!
    // This way we can delete an invoice and start the proces again
    if ($this->EcmInvoiceExists($cInvoiceID, $iConfigID)) {
      $this->log('Invoice already exists: ' . $cInvoiceID);
      return;
    }

    // For debugging only
    $this->log("Processing config: {$aConfig['Id']} for company: {$aCompany['Id']} and reseller: {$aReseller['Id']} AND Month: {$cLastMonth}");
    // First phase, aggregate the daily record counters
    $aCounters = $this->EcmConfigAggregate($iConfigID, $cLastMonth);
    // Second Phase, Create calculation
    $iSubscriptionId = $aConfig['Int_1'];
    $aCostBreakDown = $this->getCostBreakdown($iSubscriptionId, $aCounters['hits'], $aCounters['data'], $aCounters['storage']);

    // Create the invoice record
    $aInvoice = array(
      'TypeID' => 60,
      'ParentID' => $iConfigID,
      'Id' => $cInvoiceID,
      'Amount' => $aCostBreakDown['ex'],
      'Text_1' => $aCostBreakDown['table']);
    $this->IdfixStorage->SaveRecord($aInvoice);

    // Create the credit record
    $aCredit = array(
      'TypeID' => 70,
      'ParentID' => $iConfigID,
      'Id' => $cInvoiceID,
      'Percentage' => $aReseller['Int_1'],
      'Amount' => round($aCostBreakDown['ex'] * ($aReseller['Int_1'] / 100), 2),
      'Bool_1' => 0,
      'Name' => '',
      );
    $this->IdfixStorage->SaveRecord($aCredit);

    // Create mail and mail record
    $aTemplateVars = array(
      'calculation' => $aCostBreakDown['table'],
      'invoice_id' => $cInvoiceID,
      'address' => $aCompany['Text_1'],
      'amount_dollar' => '&dollar;&nbsp;' . round($aCostBreakDown['ex'] * 1.25, 2),
      );

    $cBody = $this->Idfix->RenderTemplate('BackofficeInvoice', $aTemplateVars);
    //echo $cBody;

    $cSubject = "Invoice idfixplatform.com {$cInvoiceID}";
    // Send CC to OTAP administrator
    $cCc = '';
    if ($aConfig['Name'] != $aCompany['Name']) {
      $cCc = $aConfig['Name'];
    }
    $aMail = array(
      'TypeID' => 50,
      'ParentID' => $iConfigID,
      'Id' => $cSubject,
      'Text_1' => $cBody,
      'SubTypeID' => 0,
      'RefID' => 1,
      'Name' => $aCompany['Name'],
      'cc' => $cCc,
      'bcc' => $aReseller['Char_2'],

      );
    $this->IdfixStorage->SaveRecord($aMail);

    // Only useed to send mail to ....
    $aUser = array('Name' => $aCompany['Name']);
    $this->IdfixMail->IdfixMailConfigCC = $cCc;
    $this->IdfixMail->IdfixMailConfigBCC = $aReseller['Char_2'];

    $this->IdfixMail->Mail($cBody, $cSubject, $aUser);


  }

  private function EcmConfigAggregate($iConfigID, $cLastMonth) {
    $cTableSpace = $this->IdfixStorage->GetTableSpaceName();
    $cSql = "SELECT sum(SubTypeID) as hits, CEIL(AVG(RefID)) as storage, CEIL(AVG(Int_1)) as data FROM {$cTableSpace} WHERE TypeID=30 AND parentID={$iConfigID} AND Id LIKE '{$cLastMonth}%'";
    $aCounters = $this->Database->DataQuerySingleRow($cSql);
    //$this->log($cSql);
    //$this->log($aCounters);
    return $aCounters;
  }

  private function getCostBreakdown($iSubscriptionId, $iHits, $iData, $iStorage) {
    $fTotalAmount = (float)0;
    // All we need for the calculation
    $aSubscription = $this->IdfixStorage->LoadRecord($iSubscriptionId);
    // Tableheader
    $aHeader = array(
      'Description&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
      'Included in subscription&nbsp;&nbsp;',
      'Used&nbsp;&nbsp;&nbsp;',
      'Extra&nbsp;&nbsp;',
      'Unit Price&nbsp;&nbsp;',
      'Total&nbsp;');
    $this->Table->SetHeader($aHeader);

    // First row for subscription
    $fAmount = (float)round($aSubscription['Int_1'] / 100, 2);
    $fTotalAmount += $fAmount;
    $this->Table->SetRow(array(
      'Subscription: ' . $aSubscription['Name'],
      1,
      1,
      0,
      '&euro;&nbsp;' . round(0, 2),
      '&euro;&nbsp;' . $fAmount,
      ));

    // Second row for the hits
    $iExtra = max(0, $iHits - $aSubscription['Points_Hits']);
    $fAmount = (float)round(($iExtra * $aSubscription['Add_Hits']) / 100, 2);
    $fTotalAmount += $fAmount;
    $this->Table->SetRow(array(
      'Hits',
      $aSubscription['Points_Hits'],
      $iHits,
      $iExtra,
      '&euro;&nbsp;' . round($aSubscription['Add_Hits'] / 100, 5),
      '&euro;&nbsp;' . $fAmount,
      ));

    // Third row for the data
    $iExtra = max(0, $iData - $aSubscription['Points_Data']);
    $fAmount = (float)round(($iExtra * $aSubscription['Add_Data']) / 100, 2);
    $fTotalAmount += $fAmount;
    $this->Table->SetRow(array(
      'Data',
      $aSubscription['Points_Data'],
      $iData,
      $iExtra,
      '&euro;&nbsp;' . round($aSubscription['Add_Data'] / 100, 5),
      '&euro;&nbsp;' . $fAmount,
      ));

    // Fourth row for the storage
    $iExtra = max(0, $iStorage - $aSubscription['Points_Storage']);
    $fAmount = (float)round(($iExtra * $aSubscription['Add_Storage']) / 100, 2);
    $fTotalAmount += $fAmount;
    $this->Table->SetRow(array(
      'Storage',
      $aSubscription['Points_Storage'],
      $iStorage,
      $iExtra,
      '&euro;&nbsp;' . round($aSubscription['Add_Storage'] / 100, 5),
      '&euro;&nbsp;' . $fAmount,
      ));

    // Fifth row for the total amount
    $fTotalAmount = round($fTotalAmount, 2);
    $fTotalAmountEx = $fTotalAmount;
    $this->Table->SetRow(array(
      '<strong>Total Excl. VAT</strong>',
      '',
      '',
      '',
      '',
      "----------<br /><strong>&euro;&nbsp;$fTotalAmount</strong>",
      ));

    $fAmount = (float)round((19 * $fTotalAmount) / 100, 2);
    $fTotalAmount += $fAmount;
    $this->Table->SetRow(array(
      'VAT for The Netherlands (BTW)',
      '',
      '',
      '',
      '19%',
      "&euro;&nbsp;$fAmount",
      ));

    // Fifth row for the total amount
    $fTotalAmount = round($fTotalAmount, 2);
    $this->Table->SetRow(array(
      '<strong>Total Incl. VAT</strong>',
      '',
      '',
      '',
      '',
      "----------<br /><strong>&euro;&nbsp;$fTotalAmount</strong>",
      ));


    $cTable = $this->Table->GetTable();

    //echo $cTable;

    return array(
      'table' => $cTable,
      'amount' => $fTotalAmount,
      'ex' => $fTotalAmountEx);

  }

  public function Events3IdfixActionInfoAfter(&$output) {
    $aTemplateVars = array(
      'icon1' => $this->Idfix->GetIconHTML('euro'),
      'title1' => 'Cost Breakdown Running Month',
      'body1' => $this->RenderInfoPanelCost(),
      'footer1' => 'Information on previous months can be found in your mailbox',
      );
    $output .= $this->Idfix->RenderTemplate('BackofficeInfoPanels', $aTemplateVars);
  }

  private function RenderInfoPanelCost() {
    $cReturn = '';
    // Store all old values
    $aOldConfig = $this->Idfix->aConfig;
    $cOldConfigName = $this->Idfix->cConfigName;
    $cOldEnvironment = $this->IdfixOtap->cCurrentEnvironment;

    // Set the new values
    $this->Idfix->cConfigName = self::NAME;
    $this->Idfix->aConfig = array();
    $this->IdfixOtap->cCurrentEnvironment = IdfixOtap::ENV_PROD;

    // Call the event handler
    $this->Idfix->Event('GetConfig');

    // Find the correct config record
    $aCfgRecord = $this->IdfixStorage->LoadAllRecords(20, null, '', array("Id = '{$cOldConfigName}'"), 1);
    //$this->log($aCfgRecord);
    $aCfgRecord = array_shift($aCfgRecord);
    if (isset($aCfgRecord['MainID'])) {
      $iSubscriptionId = $aCfgRecord['Int_1'];
      $iConfigID = $aCfgRecord['MainID'];
      $cMonth = date('Y-m');
      $aCounters = $this->EcmConfigAggregate($iConfigID, $cMonth);
      $aCostBreakDown = $this->getCostBreakdown($iSubscriptionId, $aCounters['hits'], $aCounters['data'], $aCounters['storage']);
      $cReturn = $aCostBreakDown['table'];
    }

    // Reset the configuration
    $this->IdfixOtap->cCurrentEnvironment = $cOldEnvironment;
    $this->Idfix->cConfigName = $cOldConfigName;
    $this->Idfix->aConfig = array();
    $this->Idfix->Event('GetConfig');

    return $cReturn;

  }

}
