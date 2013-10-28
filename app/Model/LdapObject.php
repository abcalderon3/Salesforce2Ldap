<?php
class LdapObject extends AppModel {
    public $name = 'LdapObject';
    public $useDbConfig = 'ldap'; 
    public $primaryKey = 'uid'; 
    public $useTable = 'ou=Users';
    public $defaultObjectClass = 'inetOrgPerson';
    
    public function setLdapContext() {
        $ldap = $this->getDataSource();
        if (isset($ldap->config['context']) && !empty($ldap->config['context'])) {
            $this->useTable = $ldap->config['context'];
        }
    }
    
    public function getLdapContext() {
        $ldap = $this->getDataSource();
        $ou = $this->useTable;
        $baseDN = $ldap->config['basedn'];
        $context = $ou . ',' . $baseDN;
        
        return $context;
    }
    
    public function getLdapError() {
        $ldap = $this->getDataSource();
        $lastError = $ldap->lastError();
        return $lastError;
    }
}
?>