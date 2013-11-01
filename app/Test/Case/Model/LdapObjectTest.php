<?php
App::uses('LdapObject', 'Model');

class LdapObjectTest extends CakeTestCase {
    public $fixtures = array('app.ldap_object');
    public $autoFixtures = false;

    public function setUp() {
        parent::setUp();
        $this->LdapObject = ClassRegistry::init('LdapObject');
        $this->LdapObject->useDbConfig = 'test_ldap';
        $this->LdapObject->setLdapContext();
    }

    public function testCreate() {
        $data['LdapObject'] = array(
            'objectclass' => 'inetOrgPerson',
            'cn' => 'DELETE DELETE1',
            'sn' => 'DELETE1',
            'givenname' => 'DELETE',
            'mail' => 'delete@example.com',
            'uid' => 'ddelete1'
        );
        $dn = 'uid' . '=' . $data['LdapObject']['uid'] . ',' . $this->LdapObject->getLdapContext();
        $data['LdapObject']['dn'] = $dn;
        $createResult = $this->LdapObject->save($data);
        
        $this->LdapObject->id = $dn;
        $this->LdapObject->primaryKey = 'dn';
        
        $this->assertTrue(is_array($createResult) && !empty($createResult));
    }
    
    public function testRead() {
        $data['LdapObject'] = array(
            'objectclass' => 'inetOrgPerson',
            'cn' => 'DELETE DELETE2',
            'sn' => 'DELETE2',
            'givenname' => 'DELETE',
            'mail' => 'delete@example.com',
            'uid' => 'ddelete2'
        );
        $dn = 'uid' . '=' . $data['LdapObject']['uid'] . ',' . $this->LdapObject->getLdapContext();
        $data['LdapObject']['dn'] = $dn;
        $createResult = $this->LdapObject->save($data);
        
        $this->LdapObject->id = $dn;
        $this->LdapObject->primaryKey = 'dn';
        
        $filter = '(&(objectclass=inetOrgPerson)(sn=DELETE2))';
        $retAttrs = array('dn','cn','givenName','sn', 'mail','uid');
        $readResult = $this->LdapObject->find('all', array( 'conditions' => $filter, 'fields' => $retAttrs ));
        
        $expected = array(
            'cn' => 'DELETE DELETE2',
            'uid' => 'ddelete2',
            'dn' => $dn
        );
        
        $this->assertTrue($readResult[0][0]['count'] == 1);
        $this->assertEqual($readResult[0]['LdapObject']['cn'], $expected['cn']);
        $this->assertEqual($readResult[0]['LdapObject']['uid'], $expected['uid']);
        $this->assertEqual($readResult[0]['LdapObject']['dn'], $expected['dn']);
    }
    
    public function testUpdate() {
        $data['LdapObject'] = array(
            'objectclass' => 'inetOrgPerson',
            'cn' => 'DELETE DELETE3',
            'sn' => 'DELETE3',
            'givenname' => 'DELETE',
            'mail' => 'delete@example.com',
            'uid' => 'ddelete3'
        );
        $dn = 'uid' . '=' . $data['LdapObject']['uid'] . ',' . $this->LdapObject->getLdapContext();
        $data['LdapObject']['dn'] = $dn;
        $createResult = $this->LdapObject->save($data);
        
        $this->LdapObject->id = $dn;
        $this->LdapObject->primaryKey = 'dn';
        
        $data = array(
            'LdapObject' => array(
                'mail' => 'update@example.com'
            )
        );
        $updateResult = $this->LdapObject->save($data);
        
        $this->assertEqual($updateResult, $data);
    }
    
    public function testDelete() {
        $data['LdapObject'] = array(
            'objectclass' => 'inetOrgPerson',
            'cn' => 'DELETE DELETE4',
            'sn' => 'DELETE4',
            'givenname' => 'DELETE',
            'mail' => 'delete@example.com',
            'uid' => 'ddelete4'
        );
        $dn = 'uid' . '=' . $data['LdapObject']['uid'] . ',' . $this->LdapObject->getLdapContext();
        $data['LdapObject']['dn'] = $dn;
        $createResult = $this->LdapObject->save($data);
        
        $this->LdapObject->id = $dn;
        $this->LdapObject->primaryKey = 'dn';
        
        $deleteResult = $this->LdapObject->delete($this->LdapObject->id);
        
        $this->assertTrue($deleteResult);
    }
    
    public function testClean() {
        $this->loadFixtures('LdapObject');
    }
}

?>