<?php

/**
 * This module is just a wrapper for 
 * for the object oriented field system
 */
class IdfixFields extends Events3Module {

  public function Events3IdfixAjaxDisplayField(&$aData) {
    $cType = $aData['type'];
    $cAction = 'Ajax';
    $this->_factory($cType, $cAction, $aData);
  }

  public function Events3IdfixDisplayField(&$aData) {

    $cType = $aData['type'];
    $cAction = 'Display';
    $this->_factory($cType, $cAction, $aData);
  }

  public function Events3IdfixEditField(&$aData) {
    $cType = $aData['type'];
    $cAction = 'Edit';
    $this->_factory($cType, $cAction, $aData);
  }

  private function _factory($cType, $cAction, &$aData) {
    // Static cache for the includefiles
    // saves us about 10 m.s.
    // And maybe a lot more with bigger lists :-)
    static $aFileList = array();

    $this->IdfixDebug->Profiler(__method__, 'start');
    $cDefaultClass = 'IdfixFieldsInput';
    $cOverride = $cDefaultClass . ucfirst($cType);

    // Checking for- and including files is time consuming.
    // Let's just do it once......
    if (!isset($aFileList[$cOverride])) {
      // Let's see if we have a specific implementation
      $cOverrideFile = dirname(__file__) . '/includes/' . $cOverride . '.class.php';
      if (file_exists($cOverrideFile)) {
        include_once $cOverrideFile;
      }
      $aFileList[$cOverride] = true;
    }

    // Get a reference to the right object
    if (class_exists($cOverride)) {
      $oField = $cOverride::GetInstance();
    }
    else {
      $oField = $cDefaultClass::GetInstance();
    }

    // Import the data structure
    $oField->SetData($aData);
    // Call the right method
    $cMethod = 'Get' . ucfirst($cAction);
    $oField->$cMethod();
    // And return the modified datastructure
    $aData = $oField->GetData();

    $this->IdfixDebug->Profiler(__method__, 'stop');
  }

}


/**
 * Baseclass for a simple field.
 * Fields need to be able to do the following things:
 * - Output Display value
 * - Output HTML for editing
 * - Read and validate POST information
 * 
 */
class IdfixFieldsBase extends Events3Module {
  private static $aInstances = array();
  protected $aData = array();
  public $oIdfix = null;

  // If we called on AJAX functionality..
  protected $bIsAjax = false;

  static function GetInstance() {
    $class = get_called_class();
    if (!isset(self::$aInstances[$class])) {
      self::$aInstances[$class] = new $class;
    }
    return self::$aInstances[$class];
  }

  public function SetData($aData) {
    //$this->IdfixDebug->Profiler(__method__, 'start');
    $this->aData = $aData;
    //$this->IdfixDebug->Profiler(__method__, 'stop');
  }
  public function GetData() {

    return $this->aData;
  }

  /**
   * If there is no Ajax action defined, just do a plain display
   * 
   * @return
   */
  public function GetAjax() {
    $this->bIsAjax = false;
    return $this->GetDisplay();
  }

  public function GetDisplay() {
  }

  public function GetEdit() {
  }

  /**
   * Cleanup any string for output
   * 
   * 
   * @param mixed $cText
   * @return
   */
  public function Clean($cText) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    return htmlspecialchars($cText, ENT_QUOTES, 'UTF-8');
    $this->IdfixDebug->Profiler(__method__, 'stop');
  }

  public function GetAttributes($aData) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $cReturn = '';
    static $aBlackList = array(
      'ajax',
      '__MainID',
      'cols',
      'title',
      'icon',
      'action',
      'required',
      'inline',
      '_tablename',
      '_name',
      'confirm',
      'group',
      'permissions',
      'validate',
      '_NoPP',
      'sql',
      '__RawValue',
      '__RawPostValue',
      '__SaveValue',
      '__DisplayValue',
      );
    foreach ($aBlackList as $cBlackKey) {
      unset($aData[$cBlackKey]);
    }

    // Ok, now set the description as a title for the tooltips
    if (isset($aData['description']) and $aData['description']) {
      $aData['title'] = $aData['description'];
      unset($aData['description']);
      $aData['data-toggle'] = 'tooltip';
    }

    foreach ($aData as $cKey => $cValue) {
      //if (is_numeric($cKey)) {
      //continue;
      //}
      if (!is_array($cValue) and $cValue) {
        if (strpos($cValue, '"')) {
          $cValue = str_replace('"', "'", $cValue);
        }
        $cReturn .= $cKey . '="' . $cValue . '" ';
      }
    }
    $this->IdfixDebug->Profiler(__method__, 'stop');
    //$this->IdfixDebug->Debug(__method__, get_defined_vars());
    //$this->IdfixDebug->Debug(__method__, $cReturn);
    return $cReturn;
  }

  public function GetAttributes2($aData) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $cReturn = '';
    static $aBlackList = array(
      'ajax' => 0,
      '__mainid' => 0,
      'cols' => 0,
      'title' => 0,
      'icon' => 0,
      'action' => 0,
      'required' => 0,
      'inline' => 0,
      '_tablename' => 0,
      '_name' => 0,
      'confirm' => 0,
      'group' => 0,
      'permissions' => 0,
      'validate' => 0,
      '_NoPP' => 0,
      'sql' => 0,
      '__RawValue' => 0,
      '__RawPostValue' => 0,
      '__SaveValue' => 0,
      '__DisplayValue' => 0,
      );

    $aData = array_diff_key($aData, $aBlackList);


    // Ok, now set the description as a title for the tooltips
    if (isset($aData['description']) and $aData['description']) {
      $aData['title'] = $aData['description'];
      unset($aData['description']);
      $aData['data-toggle'] = 'tooltip';
    }

    foreach ($aData as $cKey => $cValue) {
      //if (is_numeric($cKey)) {
      //continue;
      //}
      if (!is_array($cValue) and $cValue) {
        if (strpos($cValue, '"')) {
          $cValue = str_replace('"', "'", $cValue);
        }
        $cReturn .= $cKey . '="' . $cValue . '" ';
      }
    }
    $this->IdfixDebug->Profiler(__method__, 'stop');
    //$this->IdfixDebug->Debug(__method__, get_defined_vars());
    //$this->IdfixDebug->Debug(__method__, $cReturn);
    return $cReturn;
  }
  /**
   * Use this function to set elements in the data array
   * 
   * @param mixed $cElementName
   * @param mixed $cElementValue
   * @return void
   */
  public function SetDataElement($cElementName, $cElementValue) {
    if (isset($this->aData[$cElementName])) {
      $this->aData[$cElementName] .= ' ' . trim($cElementValue);
    }
    else {
      $this->aData[$cElementName] = $cElementValue;
    }
  }

  public function SetCssClass($cClass) {
    if (strpos($this->aData['class'], $cClass) === false) {
      $this->aData['class'] .= ' ' . $cClass;
    }
  }

  /**
   * Return a valid name for an html element
   * 
   * @return
   */
  public function GetName() {
    return $this->aData['_name'];
  }
  public function GetId() {
    return $this->Idfix->ValidIdentifier($this->aData['_tablename'] . '-' . $this->aData['_name']);
  }

  /**
   * Render a full form element with support for error messages.
   * All parameters are available in the template itself.
   * 
   * @param string $cTitle
   * @param string $cDescription
   * @param string $cError
   * @param string $cId
   * @param string $cInput
   * @return string rendered HTML
   */
  protected function RenderFormElement($cTitle, $cDescription, $cError, $cId, $cInput) {
    // Default set from the parameterlist
    $aTemplateVars = get_defined_vars();
    // Add the number of columns the input element uis wide
    $aTemplateVars['iColumns'] = $this->aData['cols'];

    $return = $this->Idfix->RenderTemplate('EditFormElement', $aTemplateVars);
    return $return;
  }


}

/**
 * The most basic fieldtype is a standard 
 * HTML5 input field
 * 
 */
class IdfixFieldsInput extends IdfixFieldsBase {

  public function GetAjax() {
    // Create a whitelist of allowed types for ajax edit
    $cAllowed = ';text;color;date;datetime-local;time;week;month;password;email;number;tel;url;';
    $bAllowed = (boolean)stripos($cAllowed, trim(strtolower($this->aData['type'])) . ';');

    $this->bIsAjax = false;
    if ($bAllowed) {
      $this->bIsAjax = true;
      $cUrl = $this->Idfix->GetUrl('', '', $this->aData['_name'], $this->aData['__MainID'], 0, 'ajaxupdate');
      $this->aData['onchange'] = "ajaxupdatehandler('{$cUrl}', this.value )";
      $this->GetEdit();
    }
    else {
      $this->GetDisplay();
    }


  }
  public function GetDisplay() {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $this->aData['__DisplayValue'] = $this->Clean($this->aData['__RawValue']);
    $this->IdfixDebug->Profiler(__method__, 'stop');
  }

  public function GetEdit() {
    $this->IdfixDebug->Profiler(__method__, 'start');
    // Unique CSS ID
    $cId = $this->GetId();
    // Unique form input element name
    $cName = $this->GetName();
    // Get CSS class for the input element
    $this->SetCssClass('form-control');
    $this->SetDataElement('id', $cId);
    $this->SetDataElement('name', $cName);

    // Set the value
    $this->aData['value'] = $this->GetValue();

    // Build the attributelist
    $cAttr = $this->GetAttributes($this->aData);

    // And get a reference to the input element
    $cInputBase = "<input {$cAttr}>";

    // Wrap the element in a group if it is required
    $cInput = $this->WrapRequired($cInputBase);

    // Get any validation messages
    $cError = $this->Validate();

    // Depending on the fact if we need an ajax element we will render
    // the complete element, or the basic version
    if ($this->bIsAjax) {
      $this->aData['__DisplayValue'] = $cInputBase;
    }
    else {
      $this->aData['__DisplayValue'] = $this->RenderFormElement($this->aData['title'], $this->aData['description'], $cError, $cId, $cInput);
    }


    $this->IdfixDebug->Profiler(__method__, 'stop');
  }


  /**
   * IdfixFieldsInput::Validate()
   * 
   * This method only triggers the eventhandling system.
   * Events should do the following:
   * 1. Check __RawPostValue
   * 2. If error SET __ValidationError = 1
   * 3. If error message needs to be displayed: Set __ValidationMessages[]
   * 
   * @return
   */
  protected function Validate() {
    $cError = '';

    // Only trigger the event system if really needed!!!
    if (isset($this->aData['__RawPostValue']) and !is_null($this->aData['__RawPostValue'])) {

      // Go through the validation routine if needed
      if (isset($this->aData['validate'])) {
        // Create an empty message structure
        $this->aData['__ValidationMessages'] = array();
        // Send in the data ...
        $this->Idfix->Event('ValidateField', $this->aData);
        // And check if we have messages
        if (count($this->aData['__ValidationMessages']) > 0) {
          // Render the messages into nice HTML
          $cError = $this->RenderValidationMessages($this->aData['__ValidationMessages']);
        }
      }

      // Errors detected??
      $bErrorsdetected = (isset($this->aData['__ValidationError']) and $this->aData['__ValidationError']);
      // No erros? Save the value
      if (!$bErrorsdetected) {
        $this->aData['__SaveValue'] = $this->aData['__RawPostValue'];
      }

    }
    return $cError;
  }

  /**
   * IdfixFieldsInput::RendervalidationMessages()
   * 
   * Give a nicely formatted list of messages. Could be a template later,
   * but for now, just build a plain list.
   * 
   * @param mixed $aList
   * @return void
   */
  private function RenderValidationMessages($aList) {
    return $this->Idfix->RenderTemplate('EditFormElementErrors', compact('aList'));
  }


  /**
   * Wrap the input in a group specifying its required
   * 
   * @param mixed $cInput
   * @return
   */
  protected function WrapRequired($cInput) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    // If we have an icon wrap it also ......
    $cIcon = '';
    if (isset($this->aData['icon']) and $this->aData['icon']) {
      $cIcon = $this->Idfix->GetIconHTML($this->aData['icon']);
      $cIcon = "<span class=\"input-group-addon\">{$cIcon}</span>";
    }

    $cRequired = '';
    if (isset($this->aData['required']) and $this->aData['required']) {
      $cRequired = "<span class=\"input-group-addon\">*</span>";
    }

    if ($cIcon or $cRequired) {
      $cInput = "<div class=\"input-group\">{$cIcon}{$cInput}{$cRequired}</div>";
    }

    $this->IdfixDebug->Profiler(__method__, 'stop');
    return $cInput;
  }

  /**
   * What value do we need to display in the control
   * 
   * @return string
   */
  protected function GetValue($bRaw = false) {
    $this->IdfixDebug->Profiler(__method__, 'start');
    $cValue = '';
    // Precedence for the post value
    if (isset($this->aData['__RawPostValue']) and !is_null($this->aData['__RawPostValue'])) {
      $cValue = $this->aData['__RawPostValue'];

    }
    // Second is the value from the record
    elseif (isset($this->aData['__RawValue']) and !is_null($this->aData['__RawValue'])) {
      $cValue = $this->aData['__RawValue'];
    }
    // Third is the optional default value
    elseif (isset($this->aData['value'])) {
      $cValue = $this->aData['value'];
    }

    // If the value is an array implode it to a comma separated value
    // for use in multi select elements
    if (is_array($cValue)) {
      $cValue = implode(',', $cValue);
    }

    $this->IdfixDebug->Profiler(__method__, 'stop');
    //$this->IdfixDebug->Debug(__method__, $this->aData);
    
    if ($bRaw) {
      return $cValue;
    }
    else {
      return $this->Clean($cValue);
    }

  }


}
