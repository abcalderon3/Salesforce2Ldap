<?php

class SforceObjectFixture extends CakeTestFixture {
    public $useDbConfig = 'test_sforce';
    public $import = array(
        'model' => 'SforceObject',
        'connection' => 'test_sforce'
    );
    
    public function init() {
        $this->records = array();
        for($i = 1; $i <= 2; $i++) {
            $this->records[] = array(
                'FirstName' => 'DELETE',
                'LastName' => 'DELETE'.$i,
                'Email' => 'delete@example.com',
                'type' => 'Contact'
            );
        }
        parent::init();
    }
    
    public function create($db) {
        // No table to be created. Records will be inserted in insert().
        $this->created[] = $this->useDbConfig;
        return true;
    }
    
    public function insert($db) {
        try {
            foreach ($this->records as $record) {
                $db->create(null, array_keys($record), array_values($record));
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
    
    public function drop($db) {
        // Won't drop the table. Records will be deleted in truncate().
        return true;
    }
    
    public function truncate($db) {
        try {
            $queryResult = $db->query('SELECT Id FROM Contact WHERE FirstName = \'DELETE\'');
            foreach ($queryResult->records as $record) {
                $sObject = new SObject($record);
                $db->delete($sObject->Id);
            }
        } catch (Exception $e) {
            return false;
        }
        
        return true;
        // Later add deletion of LdapObjects (or create it in LdapObjectTest)
    }
}

?>
