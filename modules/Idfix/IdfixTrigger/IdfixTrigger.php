<?php

class IdfixTrigger extends Events3Module {

  /**
   *  Events 
   */

  public function Events3IdfixInsert($aPacket) {
    $this->TriggerEvent('insert', $aPacket);
  }
  public function Events3IdfixUpdate($aPacket) {
    $this->TriggerEvent('update', $aPacket);
  }
  public function Events3IdfixDelete($aPacket) {
    $this->TriggerEvent('delete', $aPacket);
  }


  /**
   * Implementation
   */
  private function TriggerEvent($cName, $aData) {
    $this->log2($cName);
    // Do we have a registered url?
    $cUrl = $this->GetTriggerUrl($cName);
    // If not, exit ASAP, not pretty but fast....
    if (!$cUrl) {
      return;
    }

    // Get the salt needed for a signature
    $cSalt = ((isset($this->Idfix->aConfig['triggers']['salt'])) ? $this->Idfix->aConfig['triggers']['salt'] : '');
    $cSignature = md5($this->Idfix->cConfigName . $this->IdfixOtap->cCurrentEnvironment . $cName . $aData['MainID'] . $cSalt);

    // Add some system info to the packet
    $aSystemInfo = array(
      'config' => $this->Idfix->cConfigName,
      'table' => $this->Idfix->cTableName,
      'field' => $this->Idfix->cTableName,
      'caller' => (strtolower($this->Idfix->cAction) == 'rest' ? 'REST' : 'UI'),
      'trigger' => $cName,
      'url' => $cUrl, // Needed by the second level task process
      'environment' => $this->IdfixOtap->cCurrentEnvironment,
      'signature' => $cSignature,

      );
    $aData[__class__] = $aSystemInfo;
    $this->log2($aData);

    // New task system
    $_SESSION[__class__][$aData['MainID']] = $aData;
    $cUrl = $this->Idfix->GetUrl('', '', '', $aData['MainID'], null, 'Triggertask');
    $this->Idfix->GetSetClientTaskUrl($cUrl);

  }

  /**
   * Single point for the creation of the key in memcache
   * 
   * @param mixed $iId
   * @return
   */
  private function CreateKeyName($iId) {
    return __class__ . $iId;
  }


  /**
   * This event is triggerd by a background client task
   * and does the calling of the external url
   * 
   * @return void
   */
  public function Events3IdfixActionTriggertask() {
    $this->log2('Entry');

    $aPacket = (array )$_SESSION[__class__][$this->Idfix->iObject];
    unset($_SESSION[__class__][$this->Idfix->iObject]);
    
    // Check if we realy have a good packet to send
    if (isset($aPacket[__class__]['url'])) {
      $this->log2('Packet found');
      // Get it and loose it, don't need to send it
      $cUrl = $aPacket[__class__]['url'];
      unset($aPacket[__class__]['url']);
      $this->log2($cUrl);

      $data = http_build_query($aPacket);
      $context = array('http' => array('method' => 'POST', 'content' => $data));
      $context = stream_context_create($context);
      $result = file_get_contents($cUrl, false, $context);
      //$this->log2($result);

    }
    $this->log2('Exit');
    exit(0);
  }

  /**
   * Try to find a trigger in the current configuration
   * 
   * Try it first at the field level for only the update event
   * Than try at table and application level for all events: insert, update, delete
   * 
   * @param mixed $cName
   * @return void
   */
  private function GetTriggerUrl($cName) {
    $cTriggerUrl = '';

    // Application level check first
    if (isset($this->Idfix->aConfig['triggers'][$cName])) {
      $this->log2('application');
      $cTriggerUrl = $this->Idfix->aConfig['triggers'][$cName];
    }

    // Overriden by table level
    if (isset($this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['triggers'][$cName])) {
      $this->log2('table1');
      if (stripos(';insert;update;delete;', $cName . ';')) {
        $this->log2('table2');
        $cTriggerUrl = $this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['triggers'][$cName];
      }
    }

    // And field level last
    if (isset($this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['fields'][$this->Idfix->cFieldName]['triggers'][$cName])) {
      $this->log2('field1');
      if (stripos(';;update;;', $cName . ';')) {
        $this->log2('field2');
        $cTriggerUrl = $this->Idfix->aConfig['tables'][$this->Idfix->cTableName]['fields'][$this->Idfix->cFieldName]['triggers'][$cName];
      }
    }

    return $cTriggerUrl;
  }

  public function log2($cTxt) {
    $aBt = debug_backtrace(false);
    $cFunction = $aBt[1]['function'];
    parent::log($cFunction . '::' . print_r($cTxt, true));
  }

}
