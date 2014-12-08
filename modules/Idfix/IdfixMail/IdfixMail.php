<?php

use \google\appengine\api\mail\Message;

class IdfixMail extends Events3Module {
  public $IdfixMailConfigSender, $IdfixMailConfigCC, $IdfixMailConfigBCC;

  /**
   * Configuration settings that can be overruled
   * in the configurationfile
   * 
   * @param array &$aConfig Reference to the configuration array
   * @return void
   */
  public function Events3ConfigInit(&$aConfig) {
    $cKey = 'IdfixMailConfigSender';
    $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : 'admin@idfix.scrappt.com';
    $this->$cKey = $aConfig[$cKey];

    $cKey = 'IdfixMailConfigCC';
    $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : '';
    $this->$cKey = $aConfig[$cKey];

    $cKey = 'IdfixMailConfigBCC';
    $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : '';
    $this->$cKey = $aConfig[$cKey];

  }
  /**
   * IdfixMail::Mail()
   * 
   * The easiest way to use this method is just giving it a body of HTML.
   * It is than send to the current user in the context of the current configuration.
   * 
   * 
   * @param string $cBody Fully rendered HTML to send
   * @param string $cSubject Subject, default to title of the configuration
   * @param optional array $aUser Idfix Record with user information
   * @param optional array $aConfig Configuration array to act upon
   * @return void
   */
  public function Mail($cBody, $cSubject = null, $aUser = null, $aConfig = null) {
    // Most important, we need to have a user!!!
    // Otherwise we do not have an email address to send to :-()
    if (is_null($aUser) or !is_array($aUser)) {
      $aUser = $this->IdfixUser->GetSetUserObject();
    }
    if (!isset($aUser['Name']) or !$aUser['Name']) {
      // Not a valid email address found
      return;
    }

    // Now get an active configuration
    if (is_null($aConfig) or !is_array($aConfig) or !isset($aConfig['title'])) {
      $aConfig = $this->Idfix->aConfig;
    }

    // Do we have a subject? Default to tile of configuration
    $cSubject = is_null($cSubject) ? $aConfig['title'] : $cSubject;

    // What is the mail address??
    $cMailTo = $aUser['Name'];
    $cSendFrom = $this->IdfixMailConfigSender;
    $cCC = $this->IdfixMailConfigCC;
    $cBCC = $this->IdfixMailConfigBCC;


    // Use the google mail API
    try {
      $oMess = new Message();
      $oMess->addTo($cMailTo);
      $oMess->setSender($cSendFrom);
      $oMess->setSubject($cSubject);
      $oMess->setHtmlBody($cBody);
      if ($cCC) $oMess->addCc($cCC);
      if ($cBCC) $oMess->addBcc($cBCC);
      $oMess->send();
    }
    catch (exception $e) {
      $this->log($e->getMessage());
    }


  }

}
