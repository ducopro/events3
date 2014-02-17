<?php

/**
 * Database abstraction layer module
 */
class Database extends Events3Module
{
    // Reference to the PDO instance
    private $pdo = null;

    function Events3ConfigInit(&$aConfig)
    {
        $key = 'DatabasePDOConnectionString';
        if (!isset($aConfig[$key])) {
            $aConfig[$key] = 'mysql:host=Hostname;dbname=databasename';
        }
        $connect = $aConfig[$key];

        $key = 'DatabasePDOUserName';
        if (!isset($aConfig[$key])) {
            $aConfig[$key] = '';
        }
        $user = $aConfig[$key];

        $key = 'DatabasePDOUserPassword';
        if (!isset($aConfig[$key])) {
            $aConfig[$key] = '';
        }
        $pass = $aConfig[$key];

        // Let's instantiate the PDO instance
        // Note that this event is raised from the prerun event
        // of the config module
        try {
            // Create a persistent connection
            $this->pdo = new PDO($connect, $user, $pass, array(PDO::ATTR_PERSISTENT => true));
        } catch (exception $e) {
            // Do nothing for the moment.....
        }
    }



}