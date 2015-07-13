<?php

/**
 * Module die een Onepp configuratie in de gaten houdt en op basis daarvan de 1 pagina website
 * bouwt in de cache directory
 * 
 * Daarnaast een handler die op verzoek de gegenereerde pagina oplevert
 */ 

class OneppBase extends Events3Module {
   
   /**
    * Generate the website if columns/section/site is changed
    */
   public function Events3IdfixSaveRecordDone( $aRecord ) {
      if($this->IsCorrectConfig()) {
         // Select the unique ID
         $iId = $aRecord['MainID'];
         // Create a trail from parent ID's
         $aTrail = $this->Idfix->Trail($iId);
         // And get the websiteid
         $iWebSiteId = (integer) array_search( 20, $aTrail);
         // Now generate this site all over again
         $this->CreateWebsite( $iWebSiteId);   
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
   
   private function CreateWebsite( $iSiteId ) {
      $aSiteRecord = $this->IdfixStorage->LoadRecord($iSiteId);
      if(isset($aSiteRecord['MainID'])) {
         $cFullBody = $this->CreateFullBody($aSiteRecord);
         $cThemeDir = $this->GetThemeDirectory($aSiteRecord['Theme']);
         $cTemplate = $cThemeDir . 'base.php';
         
         // Render the file
         $aSiteRecord['sections'] = $cFullBody;
         $cFullPage = $this->Template->Render($cTemplate, $aSiteRecord );
         
         // And save it as a cache file
      }
   }
   
   private function GetThemeDirectory( $cName ) {
      
   }
   
   private function CreateFullBody( $aSite ){
      
   }
}