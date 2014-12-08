<?php


/**
 * All functionality for background tasks
 */

class IdfixTask extends Events3Module {

  /**
   * This action is called from the pushqueu
   * 
   * Note that we are NOT logged in!!!!
   * No session information is available
   * 
   * But every information about the configuration
   * is parsed
   * 
   * In the app.yaml file this action is protected
   * with an admin identifier
   * 
   * @return void
   */
  public function Events3IdfixActionPushtask() {

    // Create an eventname to call
    $cEvent = 'Pushtask';
    if (isset($_GET['event'])) {
      $cEvent = $cEvent . ucfirst($_GET['event']);
    }

    // And call it
    //$this->Idfix->FlashMessage("Calling {$cEvent}");
    $this->log('Pushtask event called: ' . $cEvent);
    //$this->log(debug_backtrace(false));
    $this->Idfix->Event($cEvent);


  }

  /**
   * Call this method to create a pushtask that calls a module->method
   * without logging in to the system
   * 
   * @param mixed $cConfigName
   * @param mixed $cTablename
   * @param mixed $cFieldName
   * @param mixed $iObject
   * @param mixed $iParent
   * @param mixed $cModule
   * @param mixed $cMethod
   * @return void
   */
  public function CreateTask($cConfigName, $cTablename, $cFieldName, $iObject, $iParent, $cEvent) {
    $cUrl = $this->Idfix->GetUrl($cConfigName, $cTablename, $cFieldName, $iObject, $iParent, 'pushtask');
    $this->log('Pushtask url created: ' . $cUrl);
    $this->Create($cUrl, $cEvent);
  }


  /**
   * Create a pushtask that calls a method within
   * a module.
   * 
   * All other parameters are gathered from the default
   * idfix URL.
   * 
   * @param mixed $cModule
   * @param mixed $cMethod
   * @return void
   */
  private function Create($cUrl, $cEvent) {

    // Parse the url
    $aUrl = parse_url($cUrl);
    // Path
    $cPath = $aUrl['path'];
    // Query string
    $cQuery = $aUrl['query'];
    $aQuery = array();
    parse_str($cQuery, $aQuery);
    // Add eventname
    $aQuery['event'] = $cEvent;

    $aOptions = array('method' => 'GET');

    if ($this->ev3->GAE_IsPlatform()) {
      require_once 'google/appengine/api/taskqueue/PushTask.php';
      //use \google\appengine\api\taskqueue\PushTask;
      $task = new google\appengine\api\taskqueue\PushTask($cPath, $aQuery, $aOptions);
      $task->add();

    }
    else {
       // @TODO voor LAMP stacks
    }

  }


}
