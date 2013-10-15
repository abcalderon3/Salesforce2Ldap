<?php

class SforceObject extends AppModel {

    public $hasMany = array(
        'SyncObject' => array(
            'className' => 'SyncObject'
        )
    );
    
    public $useDbConfig = 'sforce';
    public $useTable = false;

    function syncContacts() {
        $sforce = $this->getDataSource();
        $SOQL = $sforce->getConfigSOQL();
        
        // ABC3TODO: Modify to take better advantage of the batched processing from Salesforce
        $resultSet = $this->queryBatch($SOQL);
        
        $syncResults = array();
        
        foreach ($resultSet as $result) {
            $this->SyncObject->newSyncObject($result);
            $this->SyncObject->performSyncOperation();
            array_push($syncResults, $this->SyncObject->getSyncResult());
        }
        
        return $syncResults;
    }
    
    function first10account() {
        $SOQL = "SELECT 
            a.Id,
            a.Name,
            a.AccountNumber
            FROM Account a 
            WHERE  IsDeleted = Null 
            Limit 10";
        $resultSet = $this->queryBatch($SOQL);
        
        return $resultSet;
    }
    
    function getContacts() {
        $sforce = $this->getDataSource();
        $SOQL = $sforce->getConfigSOQL();
        $resultSet = $this->queryBatch($SOQL);
        
        return $resultSet;
    }

    function queryBatch($SOQL = NULL) {
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

}

?>