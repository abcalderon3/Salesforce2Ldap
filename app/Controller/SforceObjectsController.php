<?php
App::uses('AppController', 'Controller');

class SforceObjectsController extends AppController {

	var $name = 'SforceObjects';
	// define helpers
	var $helpers = array('Html', 'Form'); 
	//no db used
	// var $uses = array('Account');
        
	function index() {
            $this->SforceObject->useDbConfig = 'sforce';
		$contacts = $this->SforceObject->getContacts();
		
		
		$this->set(compact('contacts'));	
	}
	
        function syncContacts() {
            $this->SforceObject->useDbConfig = 'sforce';
            $syncResults = $this->SforceObject->syncContacts();
            
            $this->set('syncResults');
        }
}
?>