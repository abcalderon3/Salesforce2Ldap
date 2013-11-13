<?php

/**
 * SalesForceSource 
 *  
 * A Salesforce SOAP Client Datasource 
 * Connects to a Salesforce partner SOAP server using the configured wsdl file 
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
 * 
 * Original Copyright and License Statement follows. This source code was
 * modified from the original and is being redistributed, also under the MIT
 * License.
 * 
 * * Copyright 2009 Chris Roberts Ph.D, www.osxgnu.org 
 * 
 * * This library is free software: you can redistribute it and/or modify 
 * * it.  
 * 
 * * This library is distributed in the hope that it will be useful, 
 * * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
class SalesforceSource extends DataSource {

    /**
     * Description for this DataSource 
     * 
     * @var string 
     */
    public $description = 'Salesforce Partner SOAP Client DataSource';

    /**
     * The SoapClient instance 
     * 
     * @var object 
     */
    public $client = null;

    /**
     * The current connection status 
     * 
     * @var boolean 
     */
    public $connected = false;

    /**
     * The default configuration 
     * 
     * @var array 
     */
    public $_baseConfig = array(
        'wsdl' => '',
        'username' => '',
        'password' => ''
    );

    /**
     * Constructor 
     * 
     * @param array $config An array defining the configuration settings 
     */
    public function __construct($config) {
        parent::__construct($config);
        $this->connect();
    }

    public function isConnected() {
        $this->connected = (is_a($this->client, 'SforcePartnerClient') ? true : false);
        
        return $this->connected;
    }

    /**
     * Connects to the SOAP server using the wsdl in the configuration 
     * passes the salesforce credentals for login 
     * @param array $config An array defining the new configuration settings 
     * @return boolean True on success, false on failure 
     * 
     */
    public function connect() {
        if (!$this->isConnected()) {
            // ABC3TODO: Make the locations of Sforce Client libs and WSDL parameterized
            App::import('Vendor', 'soapclient/SforcePartnerClient');
            $wsdl = APP . 'Vendor/soapclient/' . $this->config['wsdl'];
            $mySforceConnection = new SforcePartnerClient();
            $mySoapClient = $mySforceConnection->createConnection($wsdl);
            $mylogin = $mySforceConnection->login($this->config['username'], $this->config['password']);
            $this->client = $mySforceConnection;
            $this->connected = true;
        }
        
        return $this->isConnected();
    }

    /**
     * Sets the SoapClient instance to null 
     * 
     * @return boolean True 
     */
    public function close() {
        $this->client = null;
        $this->connected = false;
        return true;
    }

    /**
     * Returns the available SOAP methods 
     * 
     * @return array List of SOAP methods 
     */
    public function listSources($data = null) {
        return $this->client->getFunctions();
    }

    /**
     * Query the SOAP server with the given method and parameters 
     * pass the SOQL query as the only pram 
     * @return mixed Returns the soql object array result on success, false on failure 
     */
    public function query($Query = null) {
        $this->error = false;
        try {
            $this->connect();
            $options = new QueryOptions($this->config['queryBatchSize']);
            $this->client->setQueryOptions($options);
            $response = $this->client->queryAll($Query);
            $queryResult = new QueryResult($response);
        } catch (Exception $e) {
            echo $e->faultstring;
        }
        
        if (is_a($queryResult, 'QueryResult')) {
            return $queryResult;
        } else {
            return false;
        }
    }
    
    public function queryMore(QueryResult $queryResult) {
        $this->error = false;
        try {
            // $this->connect();
            $response = $this->client->queryMore($queryResult->queryLocator);
            $queryResult = new QueryResult($response);
        } catch (Exception $e) {
            echo $e->faultstring;
        }
        
        return $queryResult;
    }

    /**
     * delete a salesforce record  
     * pass the SOQL query as the only pram 
     * @return mixed Returns the soql object array result on success, false on failure 
     */
    public function delete($Id = null) {
        $this->error = false;
        try {
            $this->connect();
            $responseArray = $this->client->delete($Id);
            $response = $responseArray[0];
        } catch (Exception $e) {
            echo $e->faultstring;
        }
        return($response->success);
    }

    /**
     * Implement the C in CRUD. Calls to ``Model::save()`` without $model->id set arrive here.
     * 
     * Creates an SObject with the data passed from Model, and then passes data to upsert().
     * Retains the idempotent nature of upsert().
     * 
     * If you want to specify the SObject type or the external ID to be used in Upsert,
     * pass them in $fields['type'] and $fields['extId'].
     * 
     * @return type
     */
    public function create($model, $fields = null, $values = null) {
        $data = array_combine($fields, $values);
        $type = 'Contact';
        if (isset($data['type'])) {
            $type = $data['type'];
            unset($data['type']);
        }
        
        try {
            $this->connect();
            $sObject = new SObject();
            $sObject->fields = $data;
            $sObject->type = $type;
            $responseArray = $this->client->create(array($sObject));
            $response = $responseArray[0];
        } catch (Exception $e) {
            echo $e->faultstring;
        }
        
        return($response->success);
    }
    
    /**
     * update the SOAP server with the given method and parameters 
     * pass the sObject query as the only pram 
     * @return mixed Returns the soql result object array result on success, false on failure 
     */
    public function upsert($localId = 'ExtId__c', array $sObjects = null) {

        $this->error = false;
        try {
            $this->connect();
            $response = $this->client->upsert($localId, $sObjects);
        } catch (Exception $e) {
            print_r($this->client->getLastRequest());
            echo $e->faultstring;
        }
        return($response->success);
    }

    /**
     * update the SOAP server with the given method and parameters 
     * pass the sObject query as the only pram 
     * @return mixed Returns the soql result object array result on success, false on failure 
     */
    public function update($sObject = null, $type = 'Contact') {
        $this->error = false;
        try {
            $this->connect();
            if (!isset($sObject->type)) {
                $sObject->type = $type;
            }
            $responseArray = $this->client->update(array($sObject));
            $response = $responseArray[0];
        } catch (Exception $e) {
            print_r($this->client->getLastRequest());
            echo $e->faultstring;
        }
        return($response->success);
    }
    
    public function fullTableName($model, $quote = true) {
        return null;
    }

    /**
     * Returns the last SOAP response 
     * 
     * @return string The last SOAP response 
     */
    public function getResponse() {
        return $this->client->getLastResponse();
    }

    /**
     * Returns the last SOAP request 
     * 
     * @return string The last SOAP request 
     */
    public function getRequest() {
        return $this->client->getLastRequest();
    }

    /**
     * Shows an error message and outputs the SOAP result if passed 
     * 
     * @param string $result A SOAP result 
     * @return string The last SOAP response 
     */
    public function showError($result = null) {
        if (Configure::read() > 0) {
            if ($this->error) {
                trigger_error('<span style = "color:Red;text-align:left"><b>SOAP Error:</b> <pre>' . print_r($this->error) . '</pre></span>', E_USER_WARNING);
            }
            if ($result) {
                e(sprintf("<p><b>Result:</b> %s </p>", $result));
            }
        }
    }
    
    /**
     * Utility function for exposing SOQL configured in the database config file
     * 
     * @return string SOQL
     */
    public function getConfigSOQL() {
        return $this->config['SOQL'];
    }

}

?>