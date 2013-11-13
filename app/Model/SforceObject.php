<?php
/**
 * Salesforce Object Model
 *
 * Interacts with the Salesforce Datasource. Performs the first step in
 * synchronization. Provides methods for querying the Salesforce datasource in
 * batches. Note:  the datasource will return all records from Salesforce,
 * including deleted records.
 *
 * PHP 5
 *
 * Copyright (c) 2013 Adrian Calderon
 *
 * LICENSE: Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt file.
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      Adrian Calderon <abc3 [at] adriancalderon [dot] org>
 * @copyright   Copyright (c) 2013 Adrian Calderon
 * @link        https://github.com/abcalderon3/Salesforce2Ldap
 * @license     http://opensource.org/licenses/MIT MIT License
 */

class SforceObject extends AppModel {

    public $hasMany = array(
        'SyncObject' => array(
            'className' => 'SyncObject'
        )
    );
    
    public $useDbConfig = 'sforce';
    public $useTable = false;

    /**
     * Performs sync operation
     * 
     * Queries Salesforce based on the configured SOQL. For each Salesforce
     * Object returned, a new SyncObject is configured and the appropriate sync
     * operation is performed.
     * 
     * @return array Array of Sforce Ids and Ldap DNs that were handled in sync
     */
    public function syncContacts() {
        // Pulls the SOQL from database.php config
        $sforce = $this->getDataSource();
        $SOQL = $sforce->getConfigSOQL();
        
        $done = false;
        $queryResult = $this->query($SOQL);
        
        $syncResults = array(
            'create' => array(),
            'update' => array(),
            'delete' => array(),
            'unchanged' => array()
        );

        // Based on the Salesforce Partner API documentation
        if ($queryResult->size > 0) {
            while (!$done) {
                foreach ($queryResult->records as $record) {
                    // Creating the sObject translates the results from the API into a useable object
                    $sObject = new SObject($record);
                    $result = get_object_vars($sObject->fields);
                    $result['Id'] = $sObject->Id;
                    // Prepares the SyncObject
                    $this->SyncObject->newSyncObject($result);
                    // Performs the appropriate sync operation
                    $this->SyncObject->performSyncOperation();
                    $syncResults = array_merge_recursive($syncResults, $this->SyncObject->getSyncResult());
                }
                if ($queryResult->done != true) {
                    $this->queryMore($queryResult);
                } else {
                    $done = true;
                }
            }
        }
        
        return $syncResults;
    }
    
    /**
     * Returns a Salesforce recordset
     * 
     * Provided for testing purposes or basic retrival. Can be used to return
     * any data from Salesforce, provided proper SOQL in database.php.
     * 
     * @return array Array of data from Salesforce
     */
    public function getContacts() {
        $sforce = $this->getDataSource();
        $SOQL = $sforce->getConfigSOQL();
        $resultSet = $this->queryBatch($SOQL);
        
        return $resultSet;
    }

    /**
     * Batched query of Salesforce data
     * 
     * Provided for basic data retrival, given some SOQL. This method will
     * efficiently retrieve data from Salesforce (in batches) and returns a
     * complete array of the recordset.
     * 
     * If further operations are being made on each record, it will not be
     * efficient to simply use the output of this method. Instead, recreate this
     * logic.
     * 
     * Based on the Salesforce Partner API documentation.
     * 
     * @param string $SOQL Any proper Salesforce SOQL
     * @return array Array of data from Salesforce
     */
    public function queryBatch($SOQL = NULL) {
        $resultSet = array();
        $done = false;

        $queryResult = $this->query($SOQL);

        if ($queryResult->size > 0) {
            while (!$done) {
                foreach ($queryResult->records as $record) {
                    $sObject = new SObject($record);
                    $result = get_object_vars($sObject->fields);
                    $result['Id'] = $sObject->Id;
                    $resultSet[] = $result;
                }
                if ($queryResult->done != true) {
                    $this->queryMore($queryResult);
                } else {
                    $done = true;
                }
            }
        }
        
        return $resultSet;
    }
    
    /**
     * Gets the current config array from the datasource
     * 
     * Retrieves configuration information from database.php.
     * 
     * @return array Configuration array from database.php
     */
    public function getSforceConfig() {
        $sforce = $this->getDataSource();
        return $sforce->config;
    }
    
    /**
     * Gets the current config parameters from the SyncObject
     * 
     * Passthrough to the current SyncObject.
     * 
     * @return array Array of sync parameters from the SyncObject
     */
    public function getSyncPara() {
        return $this->SyncObject->getSyncPara();
    }

}

?>