<?php

class SforceObjectsControllerTest extends ControllerTestCase {
    public $fixtures = array('app.sforce_object','app.ldap_object');
    
    public function testSyncContacts() {
        $result = $this->testAction('/sforce_objects/syncContacts');
        debug($result);
    }
}

?>
