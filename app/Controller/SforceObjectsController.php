<?php
/**
 * Salesforce Object Controller
 *
 * Handles the retrival of configuration parameters. Calls the functions on the
 * associated Salesforce Object Model to start the process of synchronization.
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

App::uses('AppController', 'Controller');

class SforceObjectsController extends AppController {

	public $name = 'SforceObjects';
	public $helpers = array('Html', 'Form');
        
        /**
         * Provides the configuration parameters held in database.php for running the sync.
         * 
         */
	function index() {
            $syncPara = $this->SforceObject->getSyncPara();
            $this->set(array('syncPara' => $syncPara));
	}
	
        /**
         * Performs the sync workflow.
         * 
         * Sets an array of results from the sync to the view.
         */
        function syncContacts() {
            $this->SforceObject->useDbConfig = 'sforce';
            $syncResults = $this->SforceObject->syncContacts();
            
            $this->set(array('syncResults' => $syncResults));
        }
}
?>