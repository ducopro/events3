<?php

/**
 * - Create a backup from the files + tablespace + uploaded files in a neat little package :-)
 * - Create a UI using the standard Idfix configuration system
 * - Download backup packages from the UI
 * - Restore packages from the UI
 * 
 * Nice little detail.........
 * Because all Idfix tables have the same structure data can be backupped
 * on one instance of an Idfix server and restored on an other effectively
 * copying Idfix applications.
 * 
 * SQL:
 * 
 * BACKUP:
 * $file = 'backups/mytable.sql';
 * $result = mysql_query("SELECT * INTO OUTFILE '$file' FROM `##table##`");
 * RESTORE:
 * $file = 'backups/mytable.sql';
 * $result = mysql_query("LOAD DATA INFILE '$file' INTO TABLE `##table##`");
 * 
 * FILES:
 * PharData::buildFromDirectory()
 */

class IdfixBackup extends Events3Module
{

    /**
     * Method called just before the record is saved.
     * Here we can trigger the routines for creating the backup
     * and putting the metadata in the fields array
     * 
     * @param mixed $aFields
     * @return void
     */
    public function Events3IdfixSaveRecord(&$aFields)
    {
        $aFields['Int_1'] = rand(1, 100) . ' MB';
        $aFields['Int_2'] = rand(100, 100000) . ' records';
        $aFields['RefID'] = rand(10, 1000) . ' files';
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
     * Code to be added to the [tables] section of the configuration
     * 
     * - Date
     * - Size in bytes
     * - Number of files
     * - Number of records
     * - File for download 
     * 
     * @return
     */
    private function GetTableStructure()
    {

        return array( // Backup table
                '__backup' => array(
                'title' => 'Backup',
                'description' => 'Archives containing the configuration, uploaded files and database records. ',
                'id' => '9998',
                'icon' => 'camera',
                'list' => array(
                    '_restore',
                    'Char_1',
                    'BackupFile' => 'Download',
                    'Name',
                    'Int_1',
                    'Int_2',
                    'RefID',
                    '_delete',
                    ),
                'fields' => array(

                    'Name' => array(
                        'type' => 'textfield',
                        'title' => 'Name',
                        'description' => 'Give this backup an optional, meaningfull description',
                        'readonly' => 0,
                        'autofocus' => 1),
 
                    'BackupFile' => array(
                        'type' => 'file',
                        'title' => 'Backup Archive',
                        'description' => 'You can upload your own backup archive here. Note that if you upload your own file no backup will be created, but you will be able to use the restore functions only',
                        ),

                    'Char_1' => array(
                        'type' => 'date',
                        'format' => 'd M Y',
                        'value' => 'now',
                        'title' => 'Created',
                        'readonly' => 1,
                        'cols' => 3,
                        ),


                    'Int_1' => array(
                        'type' => 'textfield',
                        'title' => 'Size',
                        'description' => 'Size of the backup in MB',
                        'readonly' => 1,
                        'cols' => 3,
                        ),

                    'Int_2' => array(
                        'type' => 'textfield',
                        'title' => 'Records',
                        'description' => 'Total number of recordsd from the tablespace',
                        'readonly' => 1,
                        'cols' => 3,
                        ),

                    'RefID' => array(
                        'type' => 'textfield',
                        'title' => 'Files',
                        'description' => 'Number of uploaded files this archive contains',
                        'readonly' => 1,
                        'cols' => 3,
                        ),

                    '_restore' => array(
                        'type' => 'virtual',
                        'title' => 'Restore',
                        'href' => $this->Idfix->GetUrl('', 'backup', 'restore', '%MainID%', null, 'restore'),
                        'description' => 'Restore this backup.',
                        'confirm' => 'Are you sure you want to restore this backup? All data from the current configuration will be destroyed. Did you create a backup before just before you clicked this button?',
                        ),


                    ),
                ), );
    }
}
