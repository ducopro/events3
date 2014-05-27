<?php

class IdfixUser extends Events3Module
{
    // These properties are filled by the configuration system
    public $IdfixUserDefault_SU_Email, $IdfixUserDefault_SU_Password;

    // False login
    private $bGoodLogin = true;

    /**
     * Check if there is a user object in the session.
     * If No user session
     *    - check if we have anonymous access
     *    - Set up anonymous user object
     * 
     * @return void
     */
    public function Events3IdfixInit()
    {
        // Only act if there is no user information at all
        // This element is UNSET at logout
        //if (!isset($_SESSION[__class__])) {
        if (!$this->IsLoggedIn()) {
            // Find a anonymous user object
            $aWhere = array('SubTypeID = 0');
            $aRecords = $this->IdfixStorage->LoadAllRecords(9999, 0, '', $aWhere, 1);
            if (count($aRecords) > 0) {
                $aUserObject = array_shift($aRecords);
                $this->GetSetUserObject($aUserObject);
            }
        }

        // If we are mnot logged in there is still a shortlist of actions we
        // are allowed to do like accessing the login page, loging in or
        // resending the password
        $bWhiteListed = in_array($this->Idfix->cAction, array(
            'Loginform',
            'Login',
            'Resend'));

        // If we still do not have a user object, redirect to the login page
        // But only redirect if we are not already trying to show this page!!!
        if (!$this->IsLoggedIn() and !$bWhiteListed) {
            $this->RedirectToLogin();
        }
    }


    /**
     * Unset everything in the navigationbar if we are not logged in
     * Only keep the branding.
     * 
     * @param mixed $data
     * @return void
     */
    public function Events3IdfixNavbarAfter(&$data)
    {
        if (!$this->IsLoggedIn()) {
            $data['left'] = array();
            $data['right'] = array();
            //echo 'not logged in';
        }
        else {
            // Add login/logout button to the navbar
            $aUser = $this->GetSetUserObject();
            $cUserName = $aUser['UserName'];
            $cIcon = $this->Idfix->GetIconHTML('user');
            $cTooltip = '';

            // Special case for anonymous visitors
            if ($this->IsAnonymous()) {
                $cHref = $this->Idfix->GetUrl($this->cConfigName, '', '', 0, 0, 'loginform');
                $cTitle = 'Login';
                $cTooltip = 'Access this application with username and password.';
            }
            else {
                $cHref = $this->Idfix->GetUrl($this->cConfigName, '', '', 0, 0, 'logout');
                $cTitle = 'Logout ' . $cUserName;
            }

            $data['right']['logout'] = array(
                'title' => $cTitle,
                'href' => $cHref,
                'icon' => $cIcon,
                'tooltip' => $cTooltip,
                );

        }
    }

    /**
     * Render the login form
     * 
     * @param mixed $output
     * @return void
     */
    public function Events3IdfixActionLoginform(&$output)
    {
        // Render Application Info panel
        $aTemplateVars = array(
            'title' => isset($this->Idfix->aConfig['title']) ? $this->Idfix->aConfig['title'] : '',
            'description' => isset($this->Idfix->aConfig['description']) ? $this->Idfix->aConfig['description'] : '',
            'icon' => $this->Idfix->GetIconHTML($this->Idfix->aConfig),
            'env' => $this->IdfixOtap->GetEnvironmentAsText(),
            );
        $app = $this->Idfix->RenderTemplate('LoginAppInfo', $aTemplateVars);

        // Render login form
        $aTemplateVars = array(
            'cPostUrl' => $this->Idfix->GetUrl('', '', '', 0, 0, 'login'),
            'bGoodLogin' => $this->bGoodLogin,
            );
        $form = $this->Idfix->RenderTemplate('LoginForm', $aTemplateVars);

        // Render Password form
        $aTemplateVars = array('cPostUrl' => $this->Idfix->GetUrl('', '', '', 0, 0, 'resend'), );
        $password = $this->Idfix->RenderTemplate('LoginPassword', $aTemplateVars);

        // Render the advanced tab with a list of configurations to choose from
        $advanced = 'No other configurations found on this server.';
        if ($this->IdfixOtap) {
            $aList = $this->IdfixOtap->GetActiveConfigList();
            $advanced = $this->Idfix->RenderTemplate('LoginAdvanced', compact('aList'));
        }

        // Render the tabular container
        $output = $this->Idfix->RenderTemplate('LoginTabs', compact('form', 'password', 'app', 'advanced'));
    }


    public function Events3IdfixActionResend()
    {
        if (isset($_POST['email'])) {
            $this->Idfix->FlashMessage('New password requested. Please check your email inbox.');
            $cEmail = $this->Database->quote($_POST['email']);
            // Find a user record
            $aWhere = array('Name = ' . $cEmail);
            $aRecords = $this->IdfixStorage->LoadAllRecords(9999, 0, '', $aWhere, 1);
            if (count($aRecords) > 0) {
                // Get the user
                $aUser = array_shift($aRecords);
                // Create new password
                $cNewPassword = substr(md5(time()), 0, 8);
                // Set it in the user object
                $aUser['Char_1'] = $cNewPassword;
                // And save it, it is automagically hashed :-)
                $this->IdfixStorage->SaveRecord($aUser);
                // Create a nice link to the loginpage
                $cLink = $this->Idfix->GetUrl('', '', '', '', '', 'loginform', array('email' => $aUser['Name'], 'password' => $cNewPassword));
                // Get the body for the mail
                $cMailBody = $this->Idfix->RenderTemplate('MailForgotPassword', compact('aUser', 'cNewPassword', 'cLink')); // Ok, let's just send it..
                // Use default subject and configuration
                $this->IdfixMail->Mail($cMailBody, null, $aUser);
            }
        }
        $this->RedirectToLogin();
    }
    /**
     * Try to login into the system
     * 
     * @return void
     */
    public function Events3IdfixActionLogin()
    {
        if (isset($_POST['email']) and isset($_POST['password'])) {
            $cEmail = $this->Database->quote($_POST['email']);
            $cPassword = $this->CreateHashValue($_POST['password']);
            $cPassword = $this->Database->quote($cPassword);
            // Find a user record
            $aWhere = array('Name = ' . $cEmail, 'Char_1 = ' . $cPassword);
            $aRecords = $this->IdfixStorage->LoadAllRecords(9999, 0, '', $aWhere, 1);
            if (count($aRecords) > 0) {
                $aUserObject = array_shift($aRecords);
                $this->GetSetUserObject($aUserObject);

                // Now redirect to the first list page
                $aTables = $this->Idfix->aConfig['tables'];
                $aFirstTable = array_shift($aTables);
                $cTableName = $aFirstTable['_name'];
                $cUrl = $this->Idfix->GetUrl('', $cTableName, '', 1, 0, 'list');
                $this->Idfix->Redirect($cUrl);

                //print_r($aUserObject);
            }
            // Set marker for the login form
            $this->bGoodLogin = $this->IsLoggedIn();
        }

    }

    public function Events3IdfixActionLogout()
    {
        unset($_SESSION[__class__]);
        $cUrl = $this->Idfix->GetUrl($this->cConfigName, '', '', 0, 0, 'loginform');
        $this->Idfix->Redirect($cUrl);

    }

    /**
     * Get or set a userobject
     * This is a rather complex routine because of the fact that we can have 
     * three different usermodes.
     * 1. Normal user for accessing only 1 OTAP part
     * 2. Administrator for accessing all parts of ONE OTAP environment
     * 3. Superuser for accessing everyting
     * 
     * @param mixed $aUser
     * @return
     */
    public function GetSetUserObject($aUser = null)
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        $cTableSpace = $this->Idfix->aConfig['tablespace'];
        $cConfigName = $this->Idfix->cConfigName;

        if (!is_null($aUser)) {
            // Store it somewhere depending on the superusermode
            $cKey = $cTableSpace;
            if ($aUser['SubTypeID'] == 2) {
                // An OTAP administrator
                $cKey = $cConfigName;
            }
            elseif ($aUser['SubTypeID'] == 3) {
                // Idfix SuperUser
                $cKey = '_idfix';
            }
            $_SESSION[__class__][$cKey] = $aUser;
        }

        // Ok, which userobject shall we return????
        $aUser = null;
        // Do we have a superuser???? Always OK!!
        if (isset($_SESSION[__class__]['_idfix'])) {
            $aUser = $_SESSION[__class__]['_idfix'];
        }
        elseif (isset($_SESSION[__class__][$cConfigName])) {
            $aUser = $_SESSION[__class__][$cConfigName];
        }
        elseif (isset($_SESSION[__class__][$cTableSpace])) {
            $aUser = $_SESSION[__class__][$cTableSpace];
        }

        $this->IdfixDebug->Profiler(__method__, 'stop');
        return $aUser;
    }

    public function Events3IdfixAccess(&$aPack)
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        $cPermission = $aPack['cPermission'];
        $bAccess = &$aPack['bAccess'];

        if ($this->IsSuperUser()) {
            $bAccess = true;
        }
        elseif ($this->IsAdministrator()) {
            $bAccess = true;
        }
        elseif ($aUser = $this->GetSetUserObject()) {
            $bAccess = !(stripos($aUser['Text_1'], $cPermission) === false);
        }
        $this->IdfixDebug->Profiler(__method__, 'stop');
    }

    /**
     * Consistent way to create a hashed value for the usersystem
     * 
     * @param mixed $cBase
     * @return
     */
    private function CreateHashValue($cBase)
    {
        return sha1($cBase . $this->Idfix->IdfixConfigSalt);
    }

    /**
     * Use this method to check if there is a logged in user.
     * 
     * @return
     */
    public function IsLoggedIn()
    {
        return !is_null($this->GetSetUserObject());
    }
    public function IsSuperUser()
    {
        if ($aUser = $this->GetSetUserObject()) {
            return ($aUser['SubTypeID'] == 3);
        }
        return false;
    }
    public function IsAdministrator()
    {
        if ($aUser = $this->GetSetUserObject()) {
            return ($aUser['SubTypeID'] == 2);
        }
        return false;
    }
    public function IsAnonymous()
    {
        if ($aUser = $this->GetSetUserObject()) {
            return ($aUser['SubTypeID'] == 0);
        }
        return false;
    }

    /**
     * Configuration settings that can be overruled
     * in the configurationfile.
     * These are used to set the default credentials for a superuser.
     * Every new table will have the defaults set
     * 
     * @param array &$aConfig Reference to the configuration array
     * @return void
     */
    public function Events3ConfigInit(&$aConfig)
    {
        $cKey = 'IdfixUserDefault_SU_Email';
        $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : 'wim.tol.tfg@gmail.com';
        $this->$cKey = $aConfig[$cKey];

        $cKey = 'IdfixUserDefault_SU_Password';
        $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : 'Russia';
        $this->$cKey = $aConfig[$cKey];
    }

    /**
     * This eventhandler is called right after the config is read from the file
     * by way of the IdfixParse module.
     * After this, default are checked and the config may be cached.
     * This is exactly the right place to add information to the configuration
     * 
     * @param array by ref $aConfig
     * @return void
     */
    public function Events3IdfixAfterParse(&$aConfig)
    {
        $aConfig['tables'] += $this->GetTableStructure();
    }

    /**
     * Idfix Event
     * Called from the SaveRecord method of the IdfixStorage module
     * 
     * Gives us the opportunity to change some fields
     * 
     * @param array $aFields Key/Value pairs Fieldname/Fieldvalue
     * @return void
     */
    public function Events3IdfixSaveRecord(&$aFields)
    {
        if (isset($aFields['TypeID']) and $aFields['TypeID'] == 9999) {
            // Only allow users to create other users with
            // the same type or lower
            // So administrators CANNOT create superusers
            // default is a normal user
            $iMaxUserTypeAllowed = 1; // Normal user

            // Store the userID in the record
            $aUser = $this->GetSetUserObject();
            if (!is_null($aUser)) {
                // Get the UserId
                $iUserId = $aUser['MainID'];
                // Set the correct values
                // Change Id can be set always
                $aFields['UidChange'] = $iUserId;
                // Only change the userid for the creator if we have a new record
                if (!isset($aFields['UidCreate'])) {
                    $aFields['UidCreate'] = $iUserId;
                }
                $iMaxUserTypeAllowed = (integer)$aUser['SubTypeID'];
            }

            // Check the usertype
            if ($aFields['SubTypeID'] > $iMaxUserTypeAllowed) {
                $aFields['SubTypeID'] = $iMaxUserTypeAllowed;
                // Inform the user
                $this->Idfix->FlashMessage('Usermode changed. You are not allowed to create that type of user.', 'warning');
            }

            // Specific postprocessing for creating the hashvalue from the password

            // Only operate on user records
            $cPassword = $aFields['Char_1'];
            $cNewPassword = $this->CreateHashValue($cPassword);
            // If the two passwords are of identical length, it means both
            // are hashvalues. In that case we do not need to do anything.
            if (strlen($cPassword) != strlen($cNewPassword)) {
                // Not identical, means we changed the password.
                $aFields['Char_1'] = $cNewPassword;
            }
        }
    }


    /**
     * Event that is called if the full configuration array is in place.
     * Here we can set up the available permissions.
     * 
     * @param mixed $aConfig
     * @return void
     */
    public function Events3IdfixGetConfigAfter()
    {
        // Create an empty permissions structure
        $aPermissions = array();
        // Call an event to fill it up
        $this->Idfix->Event('GetPermissions', $aPermissions);
        // And set it as an options array for the permissions of the
        // user table
        $this->Idfix->aConfig['tables']['__users']['fields']['Text_1']['options'] = $aPermissions;
    }

    /**
     * Add
     * 
     * @param mixed $aPerm
     * @return void
     */
    public function Events3IdfixGetPermissions(&$aPerm)
    {
        $aPerm += $this->GetPermissionsByConfig();

    }
    /**
     * Code to be added to the [tables] section of the configuration
     * 
     * @return
     */
    private function GetTableStructure()
    {

        return array( // First table: user
                '__users' => array(
                'title' => 'User',
                'description' => 'Accounts to log into the Idfix system for a specific configuration',
                'id' => '9999',
                'icon' => 'user',
                'list' => array(
                    '_edit',
                    '_copy',
                    'UserName',
                    'Name',
                    'SubTypeID' => 'Role',
                    '_delete',
                    ),
                'fields' => array(
                    'Text_1' => array(
                        'permissions' => 1,
                        'type' => 'checkboxes',
                        'title' => 'Permissions',
                        'cols' => '4',
                        'options' => array(),
                        ),
                    'SubTypeID' => array(
                        'permissions' => 1,
                        'type' => 'radios',
                        'title' => 'User Mode',
                        'description' => '<strong>If you change this mode you must logout first for the settings to take effect.</strong>',
                        'icon' => 'warning-sign',
                        'class' => 'danger',
                        'required' => 'required',
                        'cols' => '8',
                        'options' => array(
                            0 => '<strong>Anonymous Access</strong> Login not needed',
                            1 => '<strong>Normal User</strong> Login to just one OTAP environment',
                            2 => '<strong>OTAP Administrator</strong> Administer full OTAP environment',
                            3 => '<strong>Idfix Global Superuser</strong> Administer all <em>Idfix</em> configurations',
                            ),
                        ),
                    'Name' => array(
                        'type' => 'email',
                        'title' => 'Email',
                        'description' => 'What is the unique email adress for this user? This email address is used to log into the system.',
                        'placeholder' => 'Email',
                        'icon' => 'send',
                        'required' => 'required',
                        'cols' => '8',
                        ),
                    'UserName' => array(
                        'type' => 'text',
                        'title' => 'Name',
                        'description' => 'User friendly name to show up in the user-interface',
                        'placeholder' => 'Username',
                        'icon' => 'paperclip',
                        'required' => 'required',
                        'cols' => '8',
                        ),
                    'Char_1' => array(
                        'type' => 'text',
                        'title' => 'Password',
                        'description' => 'Password to log into the system. The value shown is stored in the database. It is the encryptyed value. To change the password, just delete this value and type the replacement password. It will be automaticly encrypted for you.',
                        'placeholder' => 'New Password',
                        'icon' => 'compressed',
                        'required' => 'required',
                        'cols' => '8',
                        ),


                    ),
                ), );
    }

    /**
     * Called directly after creating the Idfix table
     * Give us a nice default Superuser
     * 
     * If this event is called, we already know the characteristics of
     * the called configuration. So it is perfectly save to call the
     * standard IdfixStorage functions.
     * 
     * @param mixed $cTableSpace
     * @return void
     */
    public function Events3IdfixCreateTable($cTableSpace)
    {
        $aUser = array(
            'TypeID' => 9999,
            'Name' => $this->IdfixUserDefault_SU_Email,
            'UserName' => 'Default SuperUser',
            'Char_1' => $this->CreateHashValue($this->IdfixUserDefault_SU_Password),
            'SubTypeID' => 3,
            'ParentID' => 0,
            );
        $this->IdfixStorage->SaveRecord($aUser);
    }


    /**
     * Low level support function for getting the permissions from a single configuration
     * Note: Added for support off the Scrappt SAAS system
     *
     * @param mixed $config_name
     * @return
     */
    private function GetPermissionsByConfig()
    {
        $this->IdfixDebug->Profiler(__method__, 'start');
        $return = array();

        // Add table level permissions
        $config = $this->Idfix->aConfig;

        // Is it a valid configuration?
        if (!is_array($config['tables'])) {
            return $return;
        }


        foreach ($config['tables'] as $table_name => $table_config) {
            $table_name_user = $table_config['title'];
            $table_name_user = "<em>{$table_name_user}</em>: ";

            $return[$table_name . '_a'] = $table_name_user . 'Add records';
            $return[$table_name . '_v'] = $table_name_user . 'View records';
            $return[$table_name . '_e'] = $table_name_user . 'Edit records';
            $return[$table_name . '_d'] = $table_name_user . 'Delete records';

            // Add custom permissions
            if (isset($table_config['permissions']) and is_array($table_config['permissions'])) {
                foreach ($table_config['permissions'] as $perm_name => $perm_sql) {
                    // $perm_name is already a valid identifier
                    $key = $table_name . '_' . $perm_name;
                    $return[$key] = $table_name_user . str_replace('_', ' ', $perm_name);
                }
            }
            // Check field-level permissions
            if (isset($table_config['fields']) and is_array($table_config['fields'])) {
                foreach ($table_config['fields'] as $field_name => $field_config) {
                    $field_name_user = $field_config['title'];
                    $field_name_user = "<em><strong>{$field_name_user}</strong></em>";
                    if (isset($field_config['permissions'])) {
                        $return[$table_name . '_' . $field_name . '_v'] = $table_name_user . 'View field ' . $field_name_user;
                        $return[$table_name . '_' . $field_name . '_e'] = $table_name_user . 'Edit field ' . $field_name_user;
                        if ($field_config['type'] == 'file') {
                            // Because we are using the public file system, viewing the file means also knowing the url and
                            // the possibility of accessing it.
                            //$return[$table_name . '_' . $field_name . '_do'] = $table_name_user . 'Download file ' . $field_name_user;
                        }
                    }
                }
            }
        }
        $this->IdfixDebug->Profiler(__method__, 'stop');
        return $return;
    }

    private function RedirectToLogin()
    {
        $cUrl = $this->Idfix->GetUrl($this->cConfigName, '', '', 0, 0, 'loginform');
        $this->Idfix->Redirect($cUrl);
    }

}
