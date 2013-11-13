<?php
/**
 * LDAP Object Model
 *
 * Interacts with the LDAP Datasource. Provides methods for correctly setting
 * LDAP configuration parameters.
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

class LdapObject extends AppModel {
    public $name = 'LdapObject';
    public $useDbConfig = 'ldap'; 
    public $primaryKey = 'uid'; 
    public $useTable = 'ou=Users';
    public $defaultObjectClass = 'inetOrgPerson';
    
    /**
     * Sets the LDAP context for entries
     * 
     * Pulls the context from the datasource configuration in database.php and
     * sets the current LdapObject's context to that value.
     */
    public function setLdapContext() {
        $ldap = $this->getDataSource();
        if (isset($ldap->config['context']) && !empty($ldap->config['context'])) {
            $this->useTable = $ldap->config['context'];
        }
    }
    
    /**
     * Gets the current LDAP context
     * 
     * Should be called after setLdapContext.
     * 
     * @return string Current LDAP context with base DN
     */
    public function getLdapContext() {
        $ldap = $this->getDataSource();
        $ou = $this->useTable;
        $baseDN = $ldap->config['basedn'];
        $context = $ou . ',' . $baseDN;
        
        return $context;
    }
    
    /**
     * Gets the most recent LDAP message
     * 
     * This function will only provide the LDAP message for the most recent
     * LDAP call. I.e., if the most recent LDAP call was simply an ldap_search,
     * which often happens in the Model workflow through exists() checking, the
     * message will be Success. Thus, this might not provide adequate
     * information for debugging. (A to-do is logged to update the Datasource
     * to remedy this.)
     * 
     * @return string Message from most recent LDAP reference result
     */
    public function getLdapError() {
        $ldap = $this->getDataSource();
        $lastError = $ldap->lastError();
        return $lastError;
    }
    
    /**
     * Gets LDAP server configuration from database.php
     * 
     * @return array LDAP server configuration
     */
    public function getLdapConfig() {
        $ldap = $this->getDataSource();
        return $ldap->config;
    }
}
?>