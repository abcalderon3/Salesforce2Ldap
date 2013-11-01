<?php
App::uses('LdapObject','Model');

class LdapObjectFixture extends CakeTestFixture {
    public $useDbConfig = 'test_ldap';
    
    public $import = array(
        'model' => 'LdapObject',
        'connection' => 'test_ldap'
    );
    
    public $model;
    
    public function init() {
        parent::init();
        
        $this->model = new LdapObject();
        $this->model->useDbConfig = $this->useDbConfig;
        $this->model->setLdapContext();
    }
    
    public function create($db) {
        // No table to be created.
        $this->created[] = $this->useDbConfig;
        return true;
    }
    
    public function drop($db) {
        // Won't drop the table. Records will be deleted in truncate().
        return true;
    }
    
    public function truncate($db) {
        try {
            $filter = '(&(objectclass=inetOrgPerson)(givenName=DELETE))';
            $retAttrs = array('dn');
            $deleteUsers = $this->model->find('all', array( 'conditions' => $filter, 'fields' => $retAttrs ));
            if ($deleteUsers) {
                foreach ($deleteUsers as $deleteUser) {
                    $dn = $deleteUser['LdapObject']['dn'];
                    $this->model->id = $dn;
                    $this->model->primaryKey = 'dn';
                    $deleteResult = $db->delete($this->model);
                    if (!$deleteResult) {
                       $error = $db->lastError();
                    }
                }
            }
        } catch (Exception $e) {
            return false;
        }
        
        return true;
        // Later add deletion of LdapObjects (or create it in LdapObjectTest)
    }
    
}

?>
