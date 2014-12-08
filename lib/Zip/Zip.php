<?php

/**
 * Easy wrapper module for the ZipArchive PECL extention
 * 
 * Because we only need this in the IdfixBackup module
 * at this moment, we do not want to bother the OTAP
 * module with it.
 * 
 *  
 * 
 */

class Zip extends Events3Module {

  /**
   * Use this one to test if zipping is available on this platform
   * 
   * @return boolean true if Zipping is possible
   */
  public function Available() {
    return class_exists('ZipArchive');
  }

  /**
   * Zip::ZipDirectory()
   * 
   * @param string $cDirectory
   * @param string $cZipFilenameToCreate
   * @return boolean true if archive was created with no errors
   */
  public function ZipDirectory($cDirectory, $cZipFilenameToCreate) {
    try {
      $oZip = new ZipArchive();
      $oZip->open($cZipFilenameToCreate, ZIPARCHIVE::CREATE);
      $this->RecurseAddFolder($cDirectory, $oZip);
      $oZip->close();
    }
    catch (exception $e) {
      $this->log($e->getMessage());
      return false;
    }
    return true;
  }

  public function UnzipDirectory($cDirectory, $cZipFilenameToUse) {
    try {
      $oZip = new ZipArchive();
      $oZip->open($cZipFilenameToUse);
      $oZip->extractTo($cDirectory);
      $oZip->close();
    }
    catch (exception $e) {
      $this->log($e->getMessage());
      return false;
    }
    return true;
  }

  /**
   * Recursively add all files and the complete folderstructure to the zipfile
   * 
   * @param string $cBaseDir
   * @param object $oZip
   * @param string $cSubDir (optional use if recursed)
   * @return void
   */
  private function RecurseAddFolder($cBaseDir, $oZip, $cSubDir = null) {
    // we check if $cBaseDir has a slash at its end, if not, we append one
    $cBaseDir .= end(str_split($cBaseDir)) == "/" ? "" : "/";
    if (!is_null($cSubDir)) {
      $cSubDir .= end(str_split($cSubDir)) == "/" ? "" : "/";
    }


    // we start by going through all files in $cBaseDir
    $fHandle = opendir($cBaseDir);
    while ($cFileOrFolderName = readdir($fHandle)) {
      if ($cFileOrFolderName && $cFileOrFolderName != "." && $cFileOrFolderName != "..") {
        if (is_file($cBaseDir . $cFileOrFolderName)) {
          // if we find a file, store it
          // if we have a subfolder, store it there
          if ($cSubDir != null) {
            $oZip->addFile($cBaseDir . $cFileOrFolderName, $cSubDir . $cFileOrFolderName);
          }
          else {
            $oZip->addFile($cBaseDir . $cFileOrFolderName, $cFileOrFolderName);
          }
        }
        elseif (is_dir($cBaseDir . $cFileOrFolderName)) {
          // if we find a folder, create a folder in the zip
          $oZip->addEmptyDir($cSubDir . $cFileOrFolderName);
          // and call the function again
          $this->RecurseAddFolder($cBaseDir . $cFileOrFolderName, $oZip, $cSubDir . $cFileOrFolderName);
        }
      }
    }
  }
}
