<?php
App::uses('AppController', 'Controller');

class SforceObjectsController extends AppController {

	public $name = 'SforceObjects';
	public $helpers = array('Html', 'Form');
        
	function index() {
            $syncPara = $this->SforceObject->getSyncPara();
            $this->set(array('syncPara' => $syncPara));
	}
	
        function syncContacts() {
            $this->SforceObject->useDbConfig = 'sforce';
            $syncResults = $this->SforceObject->syncContacts();
            
            $this->set(array('syncResults' => $syncResults));
        }
}
?>