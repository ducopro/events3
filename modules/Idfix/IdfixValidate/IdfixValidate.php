<?php

/**
 * Validation Handler for the field system
 * 
 * INPUT: Field Configuration
 * 
 * TODO:
 * 1. Check __RawPostValue
 * 2. Set __ValidationError to 1 if needed
 * 3. Set optional __ValidationMessages
 * 
 * This handler is only triggered from the field system in the Validate method
 * 
 */

class IdfixValidate extends Events3Module
{

    // Reference to original field configuration
    private $aFieldConfig;
    // Create list of errormessages
    private $aMessages = array();
    // Create easy to acces value to check
    private $xValue;
    // Create easy to access validatorlist
    private $aValidators;

    public function Events3IdfixValidateField(&$aData)
    {
        // Create the reference to the original data
        $this->aFieldConfig = &$aData;
        // Create validationlist
        $this->aValidators = $aData['validate'];
        // Create value to check
        $this->xValue = $aData['__RawPostValue'];
        // Reset the validation message list
        $this->aMessages = array();

        /**
         * Now we can call all the handlers in the same format
         * 
         * Check<Handler>() {
         * Check: $this->xValue
         * Validationlist: $this->aValidators
         * Error???? Use method: SetError();
         * }
         **/
        $this->CheckRequired();
        $this->CheckEmail();
        $this->CheckIp();
        $this->CheckUrl();
        
        
        // Last action, Set the errormessages list
        $aData['__ValidationMessages'] = $this->aMessages;
    }           

    /**
     * IdfixValidate::SetError()
     * 
     * Set an error on this field
     * 
     * @param mixed $cMessage
     * @return void
     */
    private function SetError($cMessage)
    {
        $this->aFieldConfig['__ValidationError'] = 1;
        if ($cMessage) {
            $this->aMessages[] = $cMessage;
        }
    }

    /**
     * - required=Errormessage
     */
    private function CheckRequired()
    {
        if (isset($this->aValidators['required'])) {
            if (!$this->xValue) {
                $this->SetError($this->aValidators['required']);
            }
        }
    }

    /**
     * -email=ErrorMessage
     */
    private function CheckEmail()
    {
        if (isset($this->aValidators['email'])) {
            if (!filter_var($this->xValue, FILTER_VALIDATE_EMAIL)) {
                $this->SetError($this->aValidators['email']);
            }
        }
    }

    /**
     * -ip=ErrorMessage
     */
    private function CheckIp()
    {
        if (isset($this->aValidators['ip'])) {
            if (!filter_var($this->xValue, FILTER_VALIDATE_IP)) {
                $this->SetError($this->aValidators['ip']);
            }
        }
    }

    /**
     * -url=ErrorMessage
     */
    private function CheckUrl()
    {
        if (isset($this->aValidators['url'])) {
            if (!filter_var($this->xValue, FILTER_VALIDATE_URL)) {
                $this->SetError($this->aValidators['url']);
            }
        }
    }
}
