<?php

class IdfixDebug extends Events3Module {

  private $fStart = null;

  /**
   * Only set start time
   * 
   * @return void
   */
  public function __construct() {
    parent::__construct();
    $this->fStart = (float)microtime(true);
  }

  /**
   * Configuration settings that can be overruled
   * in the configurationfile
   * 
   * @param array &$aConfig Reference to the configuration array
   * @return void
   */
  public function Events3ConfigInit(&$aConfig) {
    $cKey = 'IdfixDebugShowDebugInfo';
    $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : '0';
    $this->$cKey = $aConfig[$cKey];

    // Override from URL
    if (isset($_GET['showdebuginfo'])) {
      $this->$cKey = true;
    }

    $cKey = 'IdfixDebugShowProfiler';
    $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : '0';
    $this->$cKey = $aConfig[$cKey];

    // Override from URL
    if (isset($_GET['showprofiler'])) {
      $this->$cKey = true;
    }

    $cKey = 'IdfixDebugShowSystemInfo';
    $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : '0';
    $this->$cKey = $aConfig[$cKey];

    // Override from URL
    if (isset($_GET['showsysteminfo'])) {
      $this->$cKey = true;
    }

  }

  /**
   * Make the debugging url vars persistent
   * 
   * @param mixed $aAttr
   * @return void
   */
  public function Events3IdfixGetUrl(&$aAttr) {
    //echo 'hello';
    $cKey = 'showdebuginfo';
    if (isset($_GET[$cKey])) {
      $aAttr[$cKey] = 1;
    }
    $cKey = 'showprofiler';
    if (isset($_GET[$cKey])) {
      $aAttr[$cKey] = 1;
    }
    $cKey = 'showsysteminfo';
    if (isset($_GET[$cKey])) {
      $aAttr[$cKey] = 1;
    }

  }


  /**
   * $this->Debug()
   *
   * @param $message
   *   The text to show in the display
   * @param $vars
   *   The parameters to show in the second column
   * @param $type
   *   Type of message as defined above this function
   * @return
   *   Full HTML with the complete table of messages
   */
  function Debug($message = null, $vars = null, $type = 1) {
    if (!$this->IdfixDebugShowDebugInfo) {
      return;
    }

    $this->Profiler(__method__, 'start');

    if (is_null($message)) {
      $list = (array )@$_SESSION['idfix']['debugtrail'];
      unset($_SESSION['idfix']['debugtrail']);
      return $list;
    }


    if (!isset($_SESSION['idfix']['debugtrail'])) {
      $_SESSION['idfix']['debugtrail'] = array();
    }

    if (!is_NULL($message)) {
      $messages = array(1 => 'Debug');
      $_SESSION['idfix']['debugtrail'][] = array(
        round(((float)microtime(true) - $this->fStart) * 1000, 2),
        $messages[$type],
        $message,
        '<pre>' . print_r($vars, true) . '</pre>',
        );
    }
    $this->Profiler(__method__, 'stop');
    return $_SESSION['idfix']['debugtrail'];
  }

  /**
   * idfix_footer()
   *
   * Implements hook_footer().
   *
   * @return
   *   Generated HTML
   */
  function Events3PostRun() {
    if (!$this->IdfixDebugShowProfiler and !$this->IdfixDebugShowDebugInfo and !$this->IdfixDebugShowSystemInfo) {
      return '';
    }


    if ($this->IdfixDebugShowSystemInfo) {
      $this->IdfixDebugShowDebugInfo = true;
      $this->Debug('memory_get_peak_usage()', memory_get_peak_usage() / 1024 / 1024);
      $this->Debug('memory_get_usage()', memory_get_usage() / 1024 / 1024);
      $this->Debug('$_GET', $_GET);
      $this->Debug('$_POST', $_POST);
      $this->Debug('$_Files', $_FILES);
      $this->Debug('$_Server', $_SERVER);
      $this->Debug('get_include_files', get_included_files());
      $this->Debug('$_Session', $_SESSION);
    }


    $header = array(
      'timer',
      'type',
      'message',
      'parameters',
      );
    $info = $this->Debug(null, null);
    $retval = '';
    if (is_array($info) and count($info) > 0) {
      if ($this->IdfixDebugShowDebugInfo or $this->IdfixDebugShowSystemInfo) {
        $retval .= $this->ThemeTable($header, $info);
      }
    }
    if ($this->IdfixDebugShowProfiler) {
      $retval .= $this->Profiler('', 'render');
    }

    //$retval .= '<br /><hr>';

    //echo $retval;
    echo $this->Idfix->RenderTemplate('IdfixDebug', array('content' => $retval));
    //return $retval;
  }

  /**
   * $this->Profiler()
   *
   * @param
   *   string, name of the item in the profiler list
   * @param
   *   string,'start' or 'stop'
   * @return
   *   NULL
   */
  function Profiler($name = '', $op = 'start') {
    if (!$this->IdfixDebugShowProfiler) {
      return '';
    }

    static $timers = array();
    if ($op == 'start') {
      $timers[$name]['start'] = (float)microtime(true);
      if (!isset($timers[$name]['count'])) {
        $timers[$name]['count'] = 0;
      }
      if (!isset($timers[$name]['total'])) {
        $timers[$name]['total'] = 0;
      }
      return;
    }
    elseif ($op == 'stop') {
      $timers[$name]['count']++;
      $diff = (float)microtime(true) - $timers[$name]['start'];
      $timers[$name]['total'] += (float)$diff;
      return;
    }
    elseif ($op == 'render') {
      // Add the timers from the events framework
      $timers += $this->ev3->aEventTimers;
      $header = array(
        'Name',
        'Count',
        'Time',
        );
      $data = array();
      foreach ($timers as $timer_name => $timer_data) {
        $row = array(
          $timer_name,
          $timer_data['count'],
          round($timer_data['total'] * 1000, 1));
        // Special case
        if (!($this->IdfixDebugShowProfiler == 2 and $timer_data['count'] == 1)) {
          $data[] = $row;
        }

      }
      return $this->ThemeTable($header, $data);

    }

    return null;
  }

  private function ThemeTable($aHead, $aBody) {
    //print_r(get_defined_vars());
    return $this->Idfix->RenderTemplate('ActionListMain', get_defined_vars());
  }


  /**
   * SYSLOG sectie
   * 
   * syslog is alleen in de produktieomgeving van GAE te gebruiken.
   * We maken hier een wrapper systeem dat een gewone logfile gebruikt
   * in de ontwikkelomgeving
   */

  /**
   * syslog wrapper
   * 
   * @param mixed $iLevel
   * @param mixed $cLog
   * @return void
   */
  public function Syslog($iLevel, $cLog) {
    if ($this->ev3->GAE_IsRuntime()) {
      syslog($iLevel, $cLog);
    }
    else {
      $cFileName = $this->ev3->BasePath . '/syslog.txt';
      $cTime = date('Ymd:His');
      $cLogLine = "{$cTime}-{$cLog}\r\n";
      file_put_contents($cFileName, $cLogLine, FILE_APPEND);
    }
  }
  public function SyslogInfo($cLog) {
    $this->Syslog(LOG_INFO, $cLog);
  }
  public function SyslogDebug($cLog) {
    $this->Syslog(LOG_DEBUG, $cLog);
  }

}
