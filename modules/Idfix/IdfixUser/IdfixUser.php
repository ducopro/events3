<?php

class IdfixUser extends Events3Module
{
    // These properties are filled by the configuration system
    public $IdfixUserDefault_SU_Email, $IdfixUserDefault_SU_Password;

    // False login
    private $bGoodLogin = true;

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
        }
    }
    /**
     * Postprocess the output and only show login form
     * 
     * @param mixed $output
     * @return void
     */
    public function Events3IdfixRender(&$output)
    {
        if (!$this->IsLoggedIn()) {
            $aTemplateVars = array(
                'cPostUrl' => $this->Idfix->GetUrl('', '', '', 0, 0, 'login'),
                'bGoodLogin' => $this->bGoodLogin,
                );
            $output = $this->Idfix->RenderTemplate('Login', $aTemplateVars);
        }
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
                //print_r($aUserObject);
            }

        }
        // Set marker for the login form
        $this->bGoodLogin = $this->IsLoggedIn();
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
    private function GetSetUserObject($aUser = null)
    {
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

        return $aUser;

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
                'title' => 'Users',
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
                'childs' => array('__permissions'),
                'fields' => array(
                    'Text_1' => array(
                        'type' => 'checkboxes',
                        'title' => 'Permissions',
                        'cols' => '4',
                        'options' => array(),
                        ),
                    'SubTypeID' => array(
                        'type' => 'radios',
                        'title' => 'User Mode',
                        'description' => '<strong>If you change this mode you must logout first for the settings to take effect.</strong>',
                        'icon' => 'warning-sign',
                        'class' => 'danger',
                        'required' => 'required',
                        'cols' => '8',
                        'options' => array(
                            1 => '<strong>Normal User</strong> Login to just one OTAP environment',
                            2 => '<strong>OTAP Administrator</strong> Administer full OTAP environment',
                            3 => '<strong>Idfix Global Superuser</strong> Administer all <em>Idfix</em> configurations',
                            ),
                        ),

                    'Name' => array(
                        'type' => 'text',
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
                ), // Second table: Permissions
            '__permissions' => array(
                'title' => 'Permissions',
                'description' => 'Which parts of the system is this user allowed to go?',
                'id' => '8888',
                'icon' => 'ok-circle',
                'fields' => array(
                    'Name' => array(
                        'type' => 'select',
                        'title' => 'Permission',
                        'description' => 'Choose one of the permissions',
                        'cols' => '6',
                        ),
                    'SubTypeID' => array(
                        'type' => 'checkbox',
                        'title' => 'Allowed',
                        'description' => 'When checked, this permissions is granted.',
                        'cols' => '6',
                        ),
                    ),
                ),
            );
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
        $return = array();

        // Add table level permissions
        $config = $this->Idfix->aConfig;

        // Is it a valid configuration?
        if (!is_array($config['tables'])) {
            return $return;
        }


        foreach ($config['tables'] as $table_name => $table_config) {
            $table_name_user = $table_config['title'];

            $return[$table_name . '_a'] = $this->CreatePermissionName('a', $table_name_user);
            $return[$table_name . '_v'] = $this->CreatePermissionName('v', $table_name_user);
            $return[$table_name . '_e'] = $this->CreatePermissionName('e', $table_name_user);
            $return[$table_name . '_d'] = $this->CreatePermissionName('d', $table_name_user);

            // Add custom permissions
            if (isset($table_config['permissions']) and is_array($table_config['permissions'])) {
                foreach ($table_config['permissions'] as $perm_name => $perm_sql) {
                    $return[$table_name . '_' . $perm_name . '_cu'] = $this->CreatePermissionName('cu', $table_name_user, $perm_name);
                }
            }
            // Check field-level permissions
            if (is_array($table_config['fields'])) {
                foreach ($table_config['fields'] as $field_name => $field_config) {
                    $field_name_user = $field_config['title'];
                    if (isset($field_config['permissions'])) {
                        $return[$table_name . '_' . $field_name . '_v'] = $this->CreatePermissionName('v', $table_name_user, $field_name_user);
                        $return[$table_name . '_' . $field_name . '_e'] = $this->CreatePermissionName('e', $table_name_user, $field_name_user);
                        if ($field_config['type'] == 'file') {
                            $return[$table_name . '_' . $field_name . '_do'] = $this->CreatePermissionName('do', $table_name_user, $field_name_user);
                        }
                    }
                }
            }
        }
        //print_r($return);
        return $return;
    }

    /**
     * $this->CreatePermissionName()
     *
     * @param
     *   mixed $op
     * @param
     *   mixed $config_name
     * @param
     *   mixed $tablename
     * @param
     *   string $fieldname
     * @return
     *   Name of the permission
     */
    public function CreatePermissionName($op, $tablename, $fieldname = 'All')
    {
        static $op_list = array(
            'v' => 'view',
            'a' => 'add',
            'e' => 'edit',
            'd' => 'delete',
            'do' => 'download',
            'cu' => 'custom');
        if ($fieldname !== 'All') {
            $fieldname = "<strong>{$fieldname}</strong>";
        }
        if ($op == 'cu') {
            $return = "{$tablename} {$fieldname}";
        }
        else {
            $return = "{$tablename} ({$op_list[$op]}  {$fieldname})";
        }

        return $return;
    }

}
