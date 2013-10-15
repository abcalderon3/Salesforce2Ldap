<?php
class PeopleController extends AppController {
 
    var $name = 'People';    
    var $components = array('RequestHandler');
    var $helpers = array('Form','Html','Javascript', 'Ajax');
  
    /** function add(){
        if(!empty($this->data)){
            $this->data['Person']['objectclass'] = array('top', 'organizationalperson', 'inetorgperson','person','posixaccount','shadowaccount');
 
            if($this->data['Person']['password'] == $this->data['Person']['password_confirm']){
                $this->data['Person']['userpassword'] = $this->data['Person']['password'];
                unset($this->data['Person']['password']);
                unset($this->data['Person']['password_confirm']);
             
                if(!isset($this->data['Person']['homedirectory'])&& isset($this->data['Person']['uid'])){
                    $this->data['Person']['homedirectory'] = '/home/'.$this->data['Person']['uid'];
                }
 
                $cn = $this->data['Person']['cn'];
                if ($this->Person->save($this->data)) {
                    $this->Session->setFlash($cn.' was added Successfully.');
                    $id = $this->Person->id;
                    $this->redirect(array('action' => 'view', 'id'=> $id));
                }else{
                    $this->Session->setFlash("$cn couldn't be created.");
                }
            }else{
                $this->Session->setFlash("Passwords don't match.");
            }
        }
        $attributes = array('uidnumber', 'uid', 'homedirectory');
        $preset = $this->autoSet($attributes);
        foreach($this->data['Person'] as $key => $value){
            $preset[$key] = $value; 
        }
        $this->data['Person'] = $preset;
         
        $groups = $this->Ldap->getGroups(array('cn','gidnumber'),null,'posixgroup');
        foreach($groups as $group){
            $groupList[$group['gidnumber']] = $group['cn'];
        }
        natcasesort($groupList);
        $this->set('groups',$groupList);
        $this->layout = 'people';
    }*/
 
    function view( $id ){
        // $this->loadModel('Person');
        if(!empty($id)){
            $filter = $this->Person->primaryKey."=".$id;
            $people = $this->Person->find('first', array( 'conditions'=>$filter));
            $this->set(compact('people'));
        }
        $this->layout = 'people';
    }
 
    /** function delete($id = null) {
        $this->Person->id = $id;
        return $this->Person->del($id);
    }*/
 
    /**
    *  The AuthComponent provides the needed functionality
    *  for login, so you can leave this function blank.
    */
    /** function login() {
    }
 
    function logout() {
        $this->redirect($this->LdapAuth->logout());
    }
 
    //Very Ugly, fix this.,
    function isAuthorized() {
        return true;
    }*/
 
}
?>