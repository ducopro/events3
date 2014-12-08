<?php

class Config extends Events3Module {

  public function Events3PreRun() {
    /* @var $events3 Events3 */
    $events3 = Events3::GetHandler();

    // Automatic ini-file selection
    $cFile = $this->ev3->GAE_GetIniFile();

    // If we are in the runtime mode, the ini-settings
    // are read-only..... so cacheable
    $aRuntimeCached = null;
    if ($this->ev3->GAE_IsRuntime()) {
      $aRuntimeCached = $this->ev3->CacheGet($cFile);
    }

    // Load the runtime values in a single array
    if (is_array($aRuntimeCached)) {
      $aRuntime = $aRuntimeCached;
    }
    else {
      $aRuntime = (array )@parse_ini_file($cFile);
    }

    // Let other modules inject their settings
    $cHashBefore = md5(implode('', array_keys($aRuntime)));
    $events3->Raise('ConfigInit', $aRuntime);
    $cHashAfter = md5(implode('', array_keys($aRuntime)));

    if ($this->ev3->GAE_IsRuntime()) {
        // Store the configuration in memory
        if(is_null($aRuntimeCached)) {
            $this->ev3->CacheSet($cFile, $aRuntime);
        }
    }

    // If the hashes do not match we need to write
    // the configfile again
    if ($cHashBefore != $cHashAfter) {
      $NewIni = '';
      foreach ($aRuntime as $iniKey => $iniValue) {
        if ($iniKey) {
          $NewIni .= "{$iniKey}=\"{$iniValue}\"\n";
        }

      }
      file_put_contents($cFile, $NewIni);

      // If the production inifile does not exist yet
      // let's write a default
      $cProdIniFile = $this->ev3->GAE_GetIniFile(2);
      //echo $cProdIniFile;
      if (!file_exists($cProdIniFile)) {
        file_put_contents($cProdIniFile, $NewIni);
      }

    }

  }
}
