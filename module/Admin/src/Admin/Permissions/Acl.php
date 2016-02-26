<?php

namespace Admin\Permissions;

use Zend\Permissions\Acl\Acl as ClassAcl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;

class Acl extends ClassAcl 
{
    protected $roles;
    protected $resources;
    protected $privileges;
    protected $rolePrivileges;
    
    public function __construct(array $roles, array $resources, array $privileges, array $rolePrivileges) {
        $this->roles = $roles;
        $this->resources = $resources;
        $this->privileges = $privileges;
        $this->rolePrivileges = $rolePrivileges;
        
        $this->loadRoles();
        $this->loadResources();
        $this->loadPrivileges();
    }
    
    protected function loadRoles()
    {
        foreach($this->roles as $role)
        {
            $this->addRole (new Role($role->name));
            
            if($role->isAdmin)
                $this->allow($role->name,array(),array());
        }
    }
    
    protected function loadResources()
    {
        foreach($this->resources as $resource) 
        {
            $this->addResource(new Resource($resource->name));
        }
    }
    
    protected function loadPrivileges()
    {
        foreach($this->privileges as $privilege)
        {
            foreach ($this->rolePrivileges as $rolePrivilege) {
                if($privilege->priId == $rolePrivilege->pri->priId){
                    $this->allow($rolePrivilege->rol->name, $privilege->res->name,$privilege->name);
                }
            }
        }
    }
}
