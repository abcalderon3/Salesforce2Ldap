<?php
class LdapObjectsController extends AppController {
    var $name = 'LdapObjects';     
    var $components = array('RequestHandler'); 
    var $helpers = array('Form','Html');
    
    function view( $id ){ 
        if(!empty($id)){
            // $this->loadModel('Ldapobject');
            $filter = $this->LdapObject->primaryKey."=".$id; 
            $ldapObjects = $this->LdapObject->find('first', array( 'conditions'=>$filter));
            
            $ldapError = $this->LdapObject->getLdapError();
            
            $this->set(compact('ldapObjects')); 
            $this->set('ldapError');
            
        } 
        // $this->layout = 'ldapobjects';
        
    }
}
?>