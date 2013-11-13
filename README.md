Salesforce2Ldap
===============

Salesforce2Ldap is an open source application for synchronizing data from Salesforce to an LDAP repository.

Powered by the [CakePHP 2.4](http://www.cakephp.org) MVC framework, and distributed under the MIT License.

Notes from Developer
--------------------

This application is intended as a proof-of-concept for one-way synchronization from Salesforce to LDAP. It is not recommended for use
in a production environment.

I am interested in gauging community reaction and interest for further development. If you have the opportunity to test the
application in various environments or provide your specific scenarios, I'd appreciate the feedback.

I developed this based on a particular scenario of holding identity-related information in Salesforce that was required for LDAP
authentication purposes in other applications. This provides the integration to maintain the identity data in Salesforce and provide
that data in an LDAP repository for other authentication scenarios (i.e., Contact records in Salesforce who are also end-users for
other web applications). 

Requirements
------------

* Web server (Apache recommended)
* PHP 5.3 or higher
* [Salesforce](http://www.salesforce.com) instance
* LDAP Server ([ApacheDS](http://directory.apache.org/) recommended)

Configuration Steps
-------------------

* Download the zip file from Github and extract to your web server.
* Direct your web server to use the [app/webroot](/app/webroot) folder as webroot.
* Retrieve the Partner WSDL file for your Salesforce instance. Follow [these directions](https://help.salesforce.com/apex/HTViewHelpDoc?id=dev_wsdl.htm&language=en_US).
* Copy your Partner WSDL file to [/app/Vendor/soapclient/](/app/Vendor/soapclient/). Example is provided as [partner.wsdl.xml](/app/Vendor/soapclient/partner.wsdl.xml).
* Update the configuration parameters in [database.php](/app/Config/database.php.default) (An example file is provided as "database.php.default". Rename this file as "database.php".). You will need to update the following parameters:
    * LDAP
        * Host of the LDAP Server (FQDN or IP Address)
        * Port (Default:  389)
        * Base DN
        * Login (DN of a user with CRUD permissions in LDAP)
        * Password (Password for above user)
        * Type
            * Netscape:  Use for most LDAP servers, including ApacheDS
            * OpenLDAP:  Use for OpenLDAP servers
            * ActiveDirectory:  Use for Active Directory servers (untested)
        * Version (Default:  3)
        * TLS True/False (Default: False)
    * Salesforce
        * WSDL (File name of your Salesforce Partner WSDL file, as copied above. This should only be the file name (i.e., "partner.wsdl.xml") not the entire path.)
        * User Name (User name of an administrator-level Salesforce user)
        * Password (Per Salesforce API requirements, this will be the password of the above user concatenated with the user's security token. [Click here for more information](http://www.salesforce.com/us/developer/docs/api/Content/sforce_api_concepts_security.htm).)
        * SOQL (The SOQL that pulls the correct fields you would like to sync to LDAP.)
* Update the synchronization parameters in [SyncObject.php](/app/Model/SyncObject.php) to fulfill your synchronization scenario.
    * generateCN (Set to true if you are only pulling FirstName and LastName from Salesforce and would like the application to generate a CN value of "FirstName LastName".)
    * generateUid (Set to true if you do not have a predefined Salesforce field for the object's user name and would like the application to generate a Uid value of "FirstInitialLastName".)
    * ldapObjectClass (The object class you use to create new users in LDAP. Most cases should be left to inetOrgPerson.)
    * ldapSforceIdAttr (The attribute on your LDAP object that should hold the Salesforce ID. This is required, in order to ensure unique synchronization between Salesforce objects and LDAP objects. Default is employeeNumber.)
    * syncMap (The array that maps LDAP attributes (the keys) to Salesforce fields (the values). Update this array to include more fields from Salesforce. IMPORTANT: Make sure that the fields specified here are being pulled in your SOQL in the database configuration!)
* Open your web browser to http://_your-server-address/Salesforce2Ldap/sforce_objects/.
* Check that the configuration will fit your scenario.
* Click the "Run Sync" button.

Unit Tests
----------

Unit tests can be performed through CakePHP's testing facility (good for checking your configuration). To run the tests, navigate to http://_your-server-address/Salesforce2Ldap/test.php

Links
-----

* [Github Repository](https://github.com/abcalderon3/Salesforce2Ldap)

Contact
-------

* Developer:  Adrian Calderon abc3 [at] adriancalderon [dot] org