<?php
App::uses('SforceObject', 'Model');
App::uses('LdapObject', 'Model');

class SforceObjectTest extends CakeTestCase {
    public $fixtures = array('app.sforce_object','app.ldap_object');

    public function setUp() {
        parent::setUp();
        $this->SforceObject = ClassRegistry::init('SforceObject');
        $this->SforceObject->useDbConfig = 'test_sforce';
        $this->LdapObject = ClassRegistry::init('LdapObject');
        $this->LdapObject->useDbConfig = 'test_ldap';
    }

    public function testSyncContacts() {
        $syncResult = $this->SforceObject->syncContacts();
        $filter = '(&(objectclass=inetOrgPerson)(givenName=DELETE))';
        $retAttrs = array('dn','cn','givenName','employeeNumber');
        $userExistsCheck = $this->LdapObject->find('all', array( 'conditions' => $filter, 'fields' => $retAttrs ));

        $this->assertTrue($userExistsCheck[0][0]['count'] > 0);
    }
}

?>
