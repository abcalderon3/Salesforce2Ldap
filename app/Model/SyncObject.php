<?php
/**
 * Synchronization Object Model
 *
 * Representation of the synchronization between a Salesforce object and an LDAP
 * object. Performs all data validation, translation, and synchronization.
 * Creates, updates, or deletes LDAP objects, based on detected changes in 
 * Salesforce.
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

class SyncObject extends AppModel {
    
    public $hasOne = 'LdapObject';
    
    /**
     * @var array Holds incoming Salesforce data
     * @var array Holds data as it is being mapped between Salesforce and LDAP
     * @var array Holds outgoing LDAP data
     */
    public $sforceData = array();
    protected $stagingData = array();
    public $ldapData = array();
    
    /**
     * Determines the operation to be performed during sync
     * 
     * Options:  create, update, delete, nothing, unchanged
     * 
     * @var string $syncOperation
     */
    public $syncOperation = 'nothing';
    
    /**
     * @var boolean Flag for generating the CN from FirstName and LastName
     * @var boolean Flag for generating the UID from the first letter of FirstName and LastName
     */
    public $generateCN = true;
    public $generateUid = true;
    
    /**
     * @var string Default objectclass to be used to add new LDAP objects
     * @var string Default attribute in LDAP that should hold the Sforce ID (Custom attribute is recommended)
     */
    public $ldapObjectClass = 'inetOrgPerson';
    public $ldapSforceIdAttr = 'employeeNumber';
    
    /**
     * Sets up the relationships between LDAP attributes and Salesforce fields.
     * 
     * @var array Array of matching LDAP attributes to Salesforce fields
     */
    public $syncMap = array(
        'cn' => 'FullName',
        'sn' => 'LastName',
        'givenName' => 'FirstName',
        'uid' => 'ldapUid',
        'mail' => 'Email'
    );
    
    /**
     * Sets up a new SyncObject
     * 
     * Flushes any lingering properties from previous sync operations. Prepares
     * the data required to perform the sync operation.
     * 
     * @param array $result Incoming Salesforce data
     */
    public function newSyncObject(array $result) {
        $this->_flushVars();
        $this->sforceData = $result;
        
        $this->syncMap[$this->ldapSforceIdAttr] = 'Id';
        $this->_setGenerationFlags();
        
        $this->LdapObject->setLdapContext();
        
        $this->prepareSyncOperation();
    }
    
    /**
     * Prepares for the sync operation
     * 
     * Generates any necessary data (e.g., CN and UID). Validates Salesforce data.
     * Sets the sync operation.
     * 
     * @throws CakeException Errors if invalid or inadequate Salesforce data is provided
     */
    public function prepareSyncOperation() {
        if (!$this->_transformSforceData()) {
            $this->log('SYNC: Failed to transform Salesforce data. Unable to generate LDAP attributes.', LOG_ERR);
        }
        
        // Validate the sforceData
        if ($this->validateSforceData()) {
            if (!$this->_setStagingData()) {
                $this->log('SYNC: Failed to set staging data. LDAP operations cannot continue.', LOG_ERR);
            }
        } else {
            throw new CakeException('SYNC: Invalid Salesforce data provided. Missing data that is required in LDAP.');
        }
        
        if (!$this->_setOperation()) {
            $this->log('SYNC: Operation for sync of Salesforce Id "' . $this->sforceData['Id'] . '" could not be determined.', LOG_ERR);
        }
    }
    
    /**
     * Performs the sync operation
     * 
     * Does the heavy lifting of doing either a create, update, or delete
     * operation. 
     */
    public function performSyncOperation() {
        switch ($this->syncOperation) {
            case 'create':
                // Sets up the data array
                $data['LdapObject'] = $this->stagingData;
                $dn = 'uid' . '=' . $this->stagingData['uid'] . ',' . $this->LdapObject->getLdapContext();
                $data['LdapObject']['dn'] = $dn;
                $data['LdapObject']['objectclass'] = $this->ldapObjectClass;
                
                // Save the data
                $createResult = $this->LdapObject->save($data);
                
                if ($createResult) {
                    $this->log('SYNC: Created LDAP Object: ' . $createResult['LdapObject']['dn'], LOG_INFO);
                    // Sets the properties on the LdapObject for reporting the sync result
                    $this->LdapObject->id = $dn;
                    $this->LdapObject->primaryKey = 'dn';
                } else {
                    $this->log('SYNC: Failed to create LDAP Object: ' . $dn . '. Salesforce ID: '. $this->sforceData['Id'] . '. LDAP Error: ' . $this->LdapObject->getLdapError() . '.', LOG_ERR);
                }
                break;
            case 'update':
                // Push everything to lower case, so String comparison will be accurate.
                $stagingObject = array_change_key_case($this->stagingData, CASE_LOWER);
                $ldapObject = array_change_key_case($this->ldapData, CASE_LOWER);
                // Diff the arrays. Should be efficient. Since we are doing a one way push from Salesforce
                // to LDAP, this also provides a clean way to get exactly the attributes we want to update.
                $diffObject = array_diff_assoc($stagingObject, $ldapObject);
                if (!empty($diffObject)) {
                    $data['LdapObject'] = $diffObject;
                    
                    // Save the data
                    $updateResult = $this->LdapObject->save($data);
                    
                    if ($updateResult) {
                        $this->log('SYNC: Updated LDAP Object: ' . $this->LdapObject->id, LOG_INFO);
                    } else {
                        $this->log('SYNC: Failed to update LDAP Object: ' . $this->LdapObject->id, LOG_ERR);
                    }
                } else {
                    // Sets the sync operation, so unchanged records (which is the usual case) are separately reported
                    $this->syncOperation = 'unchanged';
                    $this->log('SYNC: LDAP Object ' . $this->LdapObject->id . ' left unchanged.', LOG_INFO);
                }
                break;
            case 'delete':
                $id = $this->LdapObject->id;
                $deleteResult = $this->LdapObject->delete($this->LdapObject->id);
                if ($deleteResult) {
                    $this->log('SYNC: Deleted LDAP Object: ' . $id, LOG_INFO);
                    // Sets id on the LdapObject for reporting the sync result
                    $this->LdapObject->id = $id;
                } else {
                    $this->log('SYNC: Failed to delete LDAP Object: ' . $id , LOG_ERR);
                    $ldapError = $this->LdapObject->getLdapError();
                    if (!empty($ldapError)) {
                        $this->log($ldapError, LOG_ERR);
                    }
                }
                break;
            case 'nothing':
                $this->log('SYNC: No action performed on record: ' . $this->sforceData['Id'], LOG_DEBUG);
                break;
            default:
                $this->log('SYNC: No operation found for Salesforce Id "' . $this->sforceData['Id'] . '". This object was not synced.', LOG_INFO);
        }
    }
    
    /**
     * Validates the provided Salesforce data
     * 
     * Checks that Salesforce data is provided for each LDAP attribute to be
     * synced, as defined by the $syncMap property.
     * 
     * @return boolean Validated flag
     */
    public function validateSforceData() {
        foreach($this->syncMap as $sforceField) {
            if (!array_key_exists($sforceField, $this->sforceData)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Resets the properties on the LdapObject and SyncObject
     */
    protected function _flushVars() {
        if (isset($this->LdapObject->id)) {
            $this->LdapObject->id = null;
        }
        if (isset($this->LdapObject->primaryKey)) {
            $this->LdapObject->primaryKey = 'uid';
        }
        
        $this->sforceData = array();
        $this->stagingData = array();
        $this->ldapData = array();
        $this->syncOperation = 'nothing';
        
    }
    
    /**
     * Checks to make sure there is data for generating the CN and UID
     */
    protected function _setGenerationFlags() {
        $this->generateCN = ($this->generateCN && array_key_exists('FirstName', $this->sforceData) && array_key_exists('LastName', $this->sforceData)) ? true : false;
        
        $this->generateUid = ($this->generateUid && array_key_exists('FirstName', $this->sforceData) && array_key_exists('LastName', $this->sforceData)) ? true : false;
        
    }
    
    /**
     * Generates CN and UID, based on flags
     * 
     * @return boolean true
     */
    protected function _transformSforceData() {
        if ($this->generateCN) {
            $this->sforceData['FullName'] = $this->sforceData['FirstName'] . ' ' . $this->sforceData['LastName'];
        }
        
        if ($this->generateUid) {
            $this->sforceData['ldapUid'] = strtolower(substr($this->sforceData['FirstName'], 0, 1) . $this->sforceData['LastName']);
        }
        
        return true;
    }
    
    /**
     * Sets the Staging Data based on $syncMap
     * 
     * @return boolean Flag whether the stagingData is set
     */
    protected function _setStagingData() {
        $this->stagingData = $this->_setSyncMap($this->sforceData);
        
        return isset($this->stagingData) ? true : false;
    }
    
    /**
     * Determines which sync operation to perform
     * 
     * Queries LDAP for the exact Salesforce object provided (based on Sforce ID).
     * If an exisiting LDAP object is found that corresponds to the Salesforce ID,
     * determines whether the object has been deleted in Salesforce.
     * 
     * @return boolean Flag for whether the sync operation is set properly
     */
    protected function _setOperation() {
        $filter = '(&(objectclass='.$this->ldapObjectClass.')('.$this->ldapSforceIdAttr.'='.$this->sforceData['Id'].'))';
        $retAttrs = array_keys($this->syncMap);
        $userExistsCheck = $this->LdapObject->find('all', array( 'conditions' => $filter, 'fields' => $retAttrs ));
        if ($userExistsCheck) {
            if ($userExistsCheck[0][0]['count'] > 1) {
                $this->log('SYNC: Duplicate or corrupt data in the LDAP repository. Multiple entries found for the following Salesforce Id: ' . $this->sforceData['Id']);
                return false;
            } elseif (!is_array($userExistsCheck) || count($userExistsCheck) != 1) {
                $this->log('SYNC: Malformed response from LDAP repository. Salesforce Id: ' . $this->sforceData['Id']);
                return false;
            }
            if ($this->sforceData['IsDeleted'] == 'true') {
                $this->syncOperation = 'delete';
            } else {
                $this->syncOperation = 'update';
                $this->ldapData = $userExistsCheck[0]['LdapObject'];
            }
            $this->LdapObject->id = $userExistsCheck[0]['LdapObject']['dn'];
            $this->LdapObject->primaryKey = 'dn';
        } elseif ($this->sforceData['IsDeleted'] != 'true') {
            $this->syncOperation = 'create';
        } else {
            $this->syncOperation = 'nothing';
        }
        
        return true;
    }
    
    /**
     * Creates an array to associate LDAP attributes to Salesforce data.
     *
     * @param array $sforceData
     * @return array LDAP data mapped to Salesforce data
     */
    protected function _setSyncMap($sforceData) {
        $mappedData = array();
        foreach ($this->syncMap as $key => $value) {
            $mappedData[strtolower($key)] = $sforceData[$value];
        }
        return $mappedData;
    }
    
    /**
     * Returns a report of what the items affected.
     * 
     * @return array $syncResult
     */
    public function getSyncResult() {
        $syncResult = array(
            $this->syncOperation => array(
                $this->sforceData['Id'] => $this->LdapObject->id
            )
        );
        
        return $syncResult;
    }
    
    /**
     * Gets the current parameters used in sync
     * 
     * @return array Array of sync parameters
     */
    public function getSyncPara() {
        $syncPara = array(
            'generateCN' => $this->generateCN,
            'generateUid' => $this->generateUid,
            'ldapObjectClass' => $this->ldapObjectClass,
            'ldapSforceIdAttr' => $this->ldapSforceIdAttr,
            'syncMap' => $this->syncMap,
            'context' => $this->LdapObject->useTable
        );
        
        return $syncPara;
    }
    
}

?>