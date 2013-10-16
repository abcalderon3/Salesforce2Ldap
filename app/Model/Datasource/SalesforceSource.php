<?php

/**
 * SalesForceSource 
 *  
 * A Slaesforce SOAP Client Datasource 
 * Connects to a Salesforce enterprise SOAP server using the configured wsdl file 
 * 
 * PHP Version 5 
 * 
 * Copyright 2009 Chris Roberts Ph.D, www.osxgnu.org 
 * 
 * This library is free software: you can redistribute it and/or modify 
 * it.  
 * 
 * This library is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 * 
 * 
 *   
 * 
 */
class SalesforceSource extends DataSource {

    /**
     * Description for this DataSource 
     * 
     * @var string 
     */
    public $description = 'Salesforce Enterprise SOAP Client DataSource';

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
        return $this->client->__getFunctions();
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
        $response = false;
        $this->error = false;
        try {
            $this->connect();
            $response = $this->client->delete($Id);
        } catch (Exception $e) {
            echo $e->faultstring;
        }
        return($response);
    }

    /**
     * update the SOAP server with the given method and parameters 
     * pass the sObject query as the only pram 
     * @return mixed Returns the soql result object array result on success, false on failure 
     */
    public function upsert($localid = 'upsert', $sOBject = null, $type = 'Contact') {

        $this->error = false;
        try {
            $this->connect();
            $this->client->upsert($localid, $sObject, $type);
        } catch (Exception $e) {
            print_r($mySforceConnection->getLastRequest());
            echo $e->faultstring;
        }
        return($response);
    }

    /**
     * update the SOAP server with the given method and parameters 
     * pass the sObject query as the only pram 
     * @return mixed Returns the soql result object array result on success, false on failure 
     */
    public function update($sOBject = null, $type = 'Contact') {
        $response = false;
        $this->error = false;
        try {
            $this->connect();
            $response = $this->client->update(array($sOBject), $type);
        } catch (Exception $e) {
            print_r($mySforceConnection->getLastRequest());
            echo $e->faultstring;
        }
        return($response);
    }

    /**
     * Returns the last SOAP response 
     * 
     * @return string The last SOAP response 
     */
    public function getResponse() {
        return $this->client->__getLastResponse();
    }

    /**
     * Returns the last SOAP request 
     * 
     * @return string The last SOAP request 
     */
    public function getRequest() {
        return $this->client->__getLastRequest();
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