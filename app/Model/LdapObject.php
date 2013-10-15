<?php
class LdapObject extends AppModel {
    var $name = 'LdapObject';
    var $useDbConfig = 'ldap'; 
    var $primaryKey = 'uid'; 
    var $useTable = 'ou=tests';
    public $defaultObjectClass = 'inetOrgPerson';
    
    var $validate = array(
                'cn' => array(
                        'alphaNumeric' => array(
                                'rule' => array('custom', '/^[a-zA-Z ]+$/'),
                                'required' => true,
                                'on' => 'create',
                                'message' => 'Only Letters, Numbers and spaces    can be used for Display Name.'
                        ),
                        'between' => array(
                                'rule' => array('between', 5, 40),
                                'on' => 'create',
                                'message' => 'Between 5 to 40 characters'
                        )
                ),
                'sn' => array(
                                'rule' => array('custom', '/^[a-zA-Z]*$/'),
                                'required' => true,
                                'on' => 'create',
                                'message' => 'Only Letters and Numbers can be used for Last Name.'
                ),
                'userpassword' => array(
                                'rule' => array('minLength', '8'),
                                'message' => 'Mimimum 8 characters long.'
                ),
                'uid' => array(
                                'rule' => array('custom', '/^[a-zA-Z0-9]*$/'),
                                'required' => true,
                                'on' => 'create',
                                'message' => 'Only Letters and Numbers can be used for Username.'
                )
        );
    
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