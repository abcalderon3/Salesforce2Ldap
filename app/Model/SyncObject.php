<?php

class SyncObject extends AppModel {
    
    public $hasOne = 'LdapObject';
    
    public $sforceData = array();
    
    protected $stagingData = array();
    
    public $ldapData = array();
    
    /**
     * Determines the operation to be performed during sync
     * 
     * Options:  create, update, delete
     * 
     * @var string $syncOperation
     */
    public $syncOperation = 'create';
    
    // protected $objectDN = NULL;
    
    /**
     * Flag for generating the CN from FirstName and LastName
     * 
     * ABC3TODO: Make configurable
     * 
     * @var boolean $generateCN
     * @var boolean $generateUid
     */
    public $generateCN = true;
    public $generateUid = true;
    
    public $ldapObjectClass = 'inetOrgPerson';
    public $ldapSforceIdAttr = 'employeeNumber';
    
    public $syncMap = array(
        'cn' => 'FullName',
        'sn' => 'LastName',
        'givenName' => 'FirstName',
        'uid' => 'ldapUid',
        'mail' => 'Email'
    );
    
    public function newSyncObject(array $result) {
        $this->sforceData = $result;
        
        $this->syncMap[$this->ldapSforceIdAttr] = 'Id';
        $this->_setGenerationFlags();
        
        $this->prepareSyncOperation();
        
        
    }
    
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
    
    public function performSyncOperation() {
        switch ($this->syncOperation) {
            case 'create':
                $data['LdapObject'] = $this->stagingData;
                $dn = 'uid' . '=' . $this->stagingData['uid'] . ',' . $this->LdapObject->getLdapContext();
                $data['LdapObject']['dn'] = $dn;
                $data['LdapObject']['objectclass'] = $this->ldapObjectClass;
                $createResult = $this->LdapObject->save($data);
                if ($createResult) {
                    $this->log('SYNC: Created LDAP Object: ' . $this->LdapObject->id, LOG_INFO);
                } else {
                    $this->log('SYNC: Failed to create LDAP Object: ' . $this->LdapObject->id . '. Salesforce ID: '. $this->sforceData['Id'] . '. LDAP Error: ' . $this->LdapObject->getLdapError() . '.', LOG_ERR);
                }
                break;
            case 'update':
                // Push everything to lower case, so String comparison will be accurate.
                $stagingObject = array_change_key_case($this->stagingData, CASE_LOWER);
                $ldapObject = array_change_key_case($this->ldapData, CASE_LOWER);
                // Diff the arrays. Should be efficient. Since we are doing a one way push from SalesForce
                // to DSEE, this also provides a clean way to get exactly the attributes we want to update.
                $diffObject = array_diff_assoc($stagingObject, $ldapObject);
                if (!empty($diffObject)) {
                    $data['LdapObject'] = $diffObject;
                    $updateResult = $this->LdapObject->save($data);
                    if ($updateResult) {
                        $this->log('SYNC: Updated LDAP Object: ' . $this->LdapObject->id, LOG_INFO);
                    } else {
                        $this->log('SYNC: Failed to update LDAP Object: ' . $this->LdapObject->id, LOG_ERR);
                    }
                } else {
                    $this->log('SYNC: LDAP Object ' . $this->LdapObject->id . ' left unchanged.', LOG_INFO);
                }
                break;
            case 'delete':
                $id = $this->LdapObject->id;
                $deleteResult = $this->LdapObject->delete($this->LdapObject->id);
                if ($deleteResult) {
                    $this->log('SYNC: Deleted LDAP Object: ' . $id, LOG_INFO);
                } else {
                    $this->log('SYNC: Failed to delete LDAP Object: ' . $this->LdapObject->id, LOG_ERR);
                    $ldapError = $this->LdapObject->getLdapError();
                    if (!empty($ldapError)) {
                        $this->log($ldapError, LOG_ERR);
                    }
                }
                break;
            default:
                $this->log('SYNC: No operation found for Salesforce Id "' . $this->sforceData['Id'] . '". This object was not synced.', LOG_INFO);
        }
    }
    
    public function validateSforceData() {
        foreach($this->syncMap as $sforceField) {
            if (!array_key_exists($sforceField, $this->sforceData)) {
                return false;
            }
        }
        
        return true;
    }
    
    protected function _setGenerationFlags() {
        $this->generateCN = ($this->generateCN && array_key_exists('FirstName', $this->sforceData) && array_key_exists('LastName', $this->sforceData)) ? true : false;
        
        $this->generateUid = ($this->generateUid && array_key_exists('FirstName', $this->sforceData) && array_key_exists('LastName', $this->sforceData)) ? true : false;
        
    }
    
    protected function _transformSforceData() {
        if ($this->generateCN) {
            $this->sforceData['FullName'] = $this->sforceData['FirstName'] . ' ' . $this->sforceData['LastName'];
        }
        
        if ($this->generateUid) {
            $this->sforceData['ldapUid'] = strtolower(substr($this->sforceData['FirstName'], 0, 1) . $this->sforceData['LastName']);
        }
        
        return true;
    }
    
    protected function _setStagingData() {
        $this->stagingData = $this->_setSyncMap($this->sforceData);
        
        return isset($this->stagingData) ? true : false;
    }
    
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
        } else {
            $this->syncOperation = 'create';
        }
        
        return true;
    }
    
    /**
     * Creates an array to associate LDAP attributes to Salesforce data.
     *
     * @param array $sforceData
     * @return array $newObject
     */
    protected function _setSyncMap($sforceData) {
        $mappedData = array();
        foreach ($this->syncMap as $key => $value) {
            $mappedData[strtolower($key)] = $sforceData[$value];
        }
        return $mappedData;
    }
    
    public function getSyncResult() {
        $syncResult = array(
            $this->syncOperation => array(
                $this->sforceData['Id'] => $this->LdapObject->id
            )
        );
        
        return $syncResult;
    }
    
}

?>