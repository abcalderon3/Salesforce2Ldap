<?php

class SforceObject extends AppModel {

    public $hasMany = array(
        'SyncObject' => array(
            'className' => 'SyncObject'
        )
    );
    
    public $useDbConfig = 'sforce';
    public $useTable = false;

    public function syncContacts() {
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

        if ($queryResult->size > 0) {
            while (!$done) {
                foreach ($queryResult->records as $record) {
                    $sObject = new SObject($record);
                    $result = get_object_vars($sObject->fields);
                    $result['Id'] = $sObject->Id;
                    $this->SyncObject->newSyncObject($result);
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
    
    public function getContacts() {
        $sforce = $this->getDataSource();
        $SOQL = $sforce->getConfigSOQL();
        $resultSet = $this->queryBatch($SOQL);
        
        return $resultSet;
    }

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
    
    public function getSforceConfig() {
        $sforce = $this->getDataSource();
        return $sforce->config;
    }
    
    public function getSyncPara() {
        return $this->SyncObject->getSyncPara();
    }

}

?>