<?php

class IdfixFieldsInputAddress extends IdfixFieldsInput {

 
  public function GetDisplay() {
    $cValue = $this->aData['__RawValue'];
    $bIsSerialized = (@unserialize($cValue) !== false);
    $this->aData['__DisplayValue'] = '';

    if ($bIsSerialized) {
      $aCompound = unserialize($cValue);
      $this->aData['__DisplayValue'] = $this->Clean($aCompound['value']);
    }


  }

  public function GetEdit() {
    // Unique CSS ID
    $cId = $this->GetId();
    //$cId = 'mytextidcontrol';
    // Unique form input element name
    $cName = $this->GetName();
    // Get CSS class for the input element
    $this->SetCssClass('form-control');
    $this->SetDataElement('id', $cId);
    $this->SetDataElement('name', $cName);
    $this->SetDataElement('type', 'text');

    // Set the value
    $cValue = $this->GetValue(true);
    $bIsSerialized = (@unserialize($cValue) !== false);

    if ($bIsSerialized) {
      //print $cValue;
      $aCompound = unserialize($cValue);
      $this->aData['value'] = $aCompound['value'];
      $fLat = $aCompound['lat'];
      $fLong = $aCompound['long'];
    }
    else {
      $this->aData['value'] = $cValue;
      $fLat = '0.0';
      $fLong = '0.0';
    }


    // Build the attributelist
    $cAttr = $this->GetAttributes($this->aData);

    // And get a reference to the input element
    $cInputBase = "<input {$cAttr}>";

    // Wrap the element in a group if it is required
    $cInput = $this->WrapRequired($cInputBase);

    // Add hidden elements for storing the coordinates
    $cInput .= "<input type=\"hidden\" id=\"{$cId}_lat\" name=\"{$cId}_lat\" value=\"{$fLat}\" />";
    $cInput .= "<input type=\"hidden\" id=\"{$cId}_long\" name=\"{$cId}_long\" value=\"{$fLong}\" />";


    // Wrap everything in a div for a google map
    // @TODO

    // Get any validation messages
    $cError = $this->Validate();

    // Rebuild the save-value
    $bIsPosted = (isset($this->aData['__RawPostValue']) and !is_null($this->aData['__RawPostValue']));
    $bErrorsdetected = (isset($this->aData['__ValidationError']) and $this->aData['__ValidationError']);
    // No erros? Save the value
    if ($bIsPosted and !$bErrorsdetected) {
      $this->aData['__SaveValue'] = serialize(array(
        'value' => $this->aData['__RawPostValue'],
        'lat' => $_POST[$cId . '_lat'],
        'long' => $_POST[$cId . '_long'],
        ));


    }


    $this->aData['__DisplayValue'] = $this->RenderFormElement($this->aData['title'], $this->aData['description'], $cError, $cId, $cInput) . $this->GetJSBase($cId);

  }

  /**
   * Write the basic javascript needed for the Google maps Address
   * 
   * @param mixed $cId
   * @return
   */
  private function GetJSBase($cId) {
    static $bOnce = true;
    $cReturn = '';
    if ($bOnce) {
      // Get the javascript as heredoc

      $cReturn = <<< EOT
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places"></script>
<script>
function setup_gm_address( input_id ) {
  var input = document.getElementById( input_id );
  var autocomplete = new google.maps.places.Autocomplete(input);
  google.maps.event.addListener(autocomplete, 'place_changed', function() {
    var place = autocomplete.getPlace();
    if (place.geometry) {
      var location = place.geometry.location;
      document.getElementById( input_id + '_lat' ).value = place.geometry.location.k ;
      document.getElementById( input_id + '_long' ).value = place.geometry.location.B ;
    }  
  });
}
</script>
EOT;

      $bOnce = false;
    }

    $cFunction = $cId . '_init';
    $cJs = <<< EOT
<script>
function {$cFunction}(){
   setup_gm_address( '{$cId}' );
}
google.maps.event.addDomListener(window, 'load', {$cFunction});
</script>
EOT;


    return $cReturn . $cJs;
  }


}
